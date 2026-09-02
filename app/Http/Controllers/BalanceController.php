<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BalanceItem;
use App\Models\SourceDocument;
use App\Services\BalanceService;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BalanceController extends Controller
{
    public function index(BalanceService $balanceService, EdiXmlGeneratorService $edi)
    {
        $userId = Auth::id();

        // Si aucune année n'est en session, on met 2026 par défaut pour s'aligner avec la liasse
        $exercice = session('annee_exercice', 2026);
        if (!session()->has('annee_exercice')) {
            session(['annee_exercice' => $exercice]);
        }

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
        $request->validate([
            'balance' => 'required|file|mimes:xlsx,xls,csv',
            'annee'   => 'required|integer',
        ]);

        $exercice = $request->input('annee');
        $userId = Auth::id();
        $societe = DB::table('societes')->where('user_id', $userId)->first();

        if (!$societe) {
            return redirect()->back()->with('error', "Aucune société configurée pour ce compte.");
        }

        try {
            // Évite les doublons pour cet exercice et cette société
            BalanceItem::where('societe_id', $societe->id)
                        ->where('exercice', $exercice)
                        ->delete();

            $data = Excel::toArray([], $request->file('balance'));

            if (!empty($data[0])) {
                $count = 0;
                foreach ($data[0] as $index => $row) {
                    // Saute la ligne d'en-tête textuelle si présente
                    if ($index == 0 && !is_numeric($row[0])) {
                        continue;
                    }

                    if (isset($row[0]) && !empty(trim($row[0]))) {
                        BalanceItem::create([
                            'user_id'         => $userId,
                            'societe_id'      => $societe->id,
                            'compte'          => trim($row[0]),
                            'libelle'         => $row[1] ?? '',
                            'solde_debiteur'  => $this->cleanAmount($row[2] ?? 0),
                            'solde_crediteur' => $this->cleanAmount($row[3] ?? 0),
                            'exercice'        => $exercice,
                        ]);
                        $count++;
                    }
                }

                // L'exercice "actif" (N) reste toujours le plus récent importé.
                // Ainsi, importer une balance N-1 n'écrase pas l'affichage de N :
                // elle vient seulement alimenter la colonne "Exercice Précédent".
                $exerciceActif = (int) max($exercice, session('annee_exercice', $exercice));
                session(['annee_exercice' => $exerciceActif]);

                $libelleExercice = $exercice < $exerciceActif
                    ? "exercice précédent (N-1 : {$exercice})"
                    : "exercice {$exercice}";

                return redirect()->back()->with('success', "$count lignes importées pour {$societe->nom_societe} — {$libelleExercice}.");
            }

            return redirect()->back()->with('error', "Le fichier est vide.");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Erreur lors de l'import : " . $e->getMessage());
        }
    }

    private function cleanAmount($amount)
    {
        if (is_numeric($amount)) return $amount;
        $cleaned = str_replace([',', ' '], ['.', ''], $amount);
        return is_numeric($cleaned) ? (float)$cleaned : 0;
    }
}
