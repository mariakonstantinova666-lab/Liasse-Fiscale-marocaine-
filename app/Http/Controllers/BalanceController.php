<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BalanceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BalanceController extends Controller
{
    public function index()
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
        if ($societe && $exercice) {
            $items = BalanceItem::where('societe_id', $societe->id)
                                ->where('exercice', $exercice)
                                ->get();
        }

        // On retourne le composant Vue "Dashboard" via Inertia
        return Inertia::render('Dashboard', [
            'items' => $items,
            'societe' => $societe,
            'exerciceActif' => $exercice,
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

                // Sauvegarde immédiate en session pour alimenter tous les tableaux (Vue et Blade)
                session(['annee_exercice' => $exercice]);
                
                return redirect()->back()->with('success', "$count lignes importées avec succès pour {$societe->nom_societe} !");
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