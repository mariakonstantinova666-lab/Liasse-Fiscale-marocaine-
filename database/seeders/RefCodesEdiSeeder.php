<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RefCodesEdiSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('import_edi.csv');

        if (!File::exists($file)) {
            $this->command->error("Fichier import_edi.csv non trouvé !");
            return;
        }

        $this->command->info("Importation des codes EDI en cours...");

        $data = file($file);
        $firstLine = true;
        $count = 0;

        foreach ($data as $line) {
            if ($firstLine) {
                $firstLine = false;
                continue;
            }

            $row = str_getcsv($line, ';');

            // On ne traite la ligne que si le premier élément (code_edi) est présent et numérique
            if (isset($row[0]) && is_numeric(trim($row[0]))) {
                $codeEdi = trim($row[0]);

                // updateOrInsert évite l'erreur "Duplicate entry"
                DB::table('ref_codes_edi')->updateOrInsert(
                    ['code_edi' => $codeEdi], // Condition pour trouver le doublon
                    [
                        'tableau'    => trim($row[1] ?? ''),
                        'libelle'    => trim($row[2] ?? ''),
                        'col1'       => trim($row[3] ?? ''),
                        'col2'       => trim($row[4] ?? ''),
                        'col3'       => trim($row[5] ?? ''),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $count++;
            } else {
                // Optionnel : affiche une alerte si une ligne est ignorée (comme celle avec "DIFFERES")
                if(!empty(trim($row[0]))) {
                    $this->command->warn("Ligne ignorée (code non valide) : " . $row[0]);
                }
            }
        }

        $this->command->info("Succès : $count codes EDI traités.");
    }
}