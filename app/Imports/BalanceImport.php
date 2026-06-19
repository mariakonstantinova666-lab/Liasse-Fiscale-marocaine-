<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BalanceImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $data = [];
        
        // On boucle sur chaque ligne du fichier Excel
        foreach ($rows as $index => $row) {
            // On ignore la ligne d'en-tête (Compte, Intitulé, etc.)
            if ($index == 0 || empty($row[0])) continue;

            $data[] = [
                'compte'  => (string)$row[0], // Colonne A : N° de compte
                'libelle' => $row[1],         // Colonne B : Intitulé
                'debit'   => (float)($row[2] ?? 0),  // Colonne C : Débit
                'credit'  => (float)($row[3] ?? 0),  // Colonne D : Crédit
            ];
        }

        // On stocke tout dans la session pour que les tableaux y accèdent
        session(['balance_data' => $data]);
    }
}