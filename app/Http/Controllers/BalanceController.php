<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BalanceItem;
use App\Models\SourceDocument;
use App\Services\BalanceService;
use App\Services\ActiveExerciceService;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BalanceController extends Controller
{
    public function index(
        BalanceService $balanceService,
        EdiXmlGeneratorService $edi,
        ActiveExerciceService $activeExercice
    )
    {
        $userId = Auth::id();

        $exercice = $activeExercice->current();

        // Récupère la société liée à l'utilisateur
        $societe = DB::table('societes')->where('user_id', $userId)->first();

        $items = [];
        $itemsPrecedent = [];
        $exercicesImportes = [];
        $sourceDocumentsCount = 0;
        $liasseDataCount = 0;
        $controlStatus = null;
        $hasGeneratedEdi = false;
        if ($societe && $exercice) {
            $items = BalanceItem::where('societe_id', $societe->id)
                                ->where('exercice', $exercice)
                                ->get();

            $itemsPrecedent = BalanceItem::where('societe_id', $societe->id)
                                ->where('exercice', $exercice - 1)
                                ->get();

            // Années déjà importées : pilote le bandeau d'historisation N / N-1
            $exercicesImportes = $balanceService->exercicesImportes($societe->id);

            $sourceDocumentsCount = SourceDocument::where('user_id', $userId)
                ->where('societe_id', $societe->id)
                ->where('exercice', $exercice)
                ->count();

            $ediContext = $edi->context($userId, (int) $exercice);
            $liasseDataCount = $ediContext['liasseData']->count();
            $controls = collect($ediContext['controls']);
            $controlStatus = $controls->isEmpty()
                ? null
                : ($controls->contains(fn ($rule) => $rule['bloquant'] && !$rule['ok'])
                    ? 'blocking'
                    : ($controls->contains(fn ($rule) => !$rule['ok']) ? 'warning' : 'compliant'));

            $ediFilenamePrefix = "liasse_edi_dgi_{$exercice}_";
            $hasGeneratedEdi = collect(Storage::disk('local')->files("edi/{$userId}"))
                ->contains(fn ($path) => str_starts_with(basename($path), $ediFilenamePrefix));
        }

        // On retourne le composant Vue "Dashboard" via Inertia
        return Inertia::render('Dashboard', [
            'items' => $items,
            'itemsPrecedent' => $itemsPrecedent,
            'societe' => $societe,
            'exerciceActif' => $exercice,
            'exercicePrecedent' => $exercice - 1,
            'exercicesImportes' => $exercicesImportes,
            'sourceDocumentsCount' => $sourceDocumentsCount,
            'liasseDataCount' => $liasseDataCount,
            'controlStatus' => $controlStatus,
            'hasGeneratedEdi' => $hasGeneratedEdi,
            'flash' => [
                'success' => session('success'),
                'error' => session('error')
            ]
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'balance' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'annee' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 10)],
        ]);

        $exercice = (int) $validated['annee'];
        $userId = Auth::id();
        $societe = DB::table('societes')->where('user_id', $userId)->first();

        if (!$societe) {
            return redirect()->back()->with('error', "Aucune société configurée pour ce compte.");
        }

        try {
            $data = Excel::toArray([], $request->file('balance'));
            $lignes = [];

            if (!empty($data[0])) {
                foreach ($data[0] as $index => $row) {
                    // Saute la ligne d'en-tête textuelle si présente
                    if ($index == 0 && !is_numeric($row[0])) {
                        continue;
                    }

                    if (isset($row[0]) && !empty(trim($row[0]))) {
                        $lignes[] = [
                            'user_id'         => $userId,
                            'societe_id'      => $societe->id,
                            'compte'          => trim($row[0]),
                            'libelle'         => $row[1] ?? '',
                            'solde_debiteur'  => $this->cleanAmount($row[2] ?? 0),
                            'solde_crediteur' => $this->cleanAmount($row[3] ?? 0),
                            'exercice'        => $exercice,
                        ];
                    }
                }
            }

            if ($lignes === []) {
                return redirect()->back()->with('error', "Le fichier ne contient aucune ligne de balance importable.");
            }

            DB::transaction(function () use ($userId, $societe, $exercice, $lignes) {
                BalanceItem::where('user_id', $userId)
                    ->where('societe_id', $societe->id)
                    ->where('exercice', $exercice)
                    ->delete();

                foreach ($lignes as $ligne) {
                    BalanceItem::create($ligne);
                }
            });

            $count = count($lignes);

            return redirect()->back()->with('success', "$count lignes importées pour {$societe->nom_societe} — exercice {$exercice}.");

        } catch (\Exception) {
            return redirect()->back()->with('error', "Erreur lors de l'import. La balance existante a été conservée.");
        }
    }

    private function cleanAmount($amount)
    {
        if (is_numeric($amount)) return $amount;
        $cleaned = str_replace([',', ' '], ['.', ''], $amount);
        return is_numeric($cleaned) ? (float)$cleaned : 0;
    }
}
