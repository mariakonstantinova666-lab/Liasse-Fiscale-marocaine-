<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PlanComptableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chemin exact basé sur ta capture d'écran : database/data/
        $file = database_path('data/plan_pour_phpmyadmin.csv');

        if (!File::exists($file)) {
            $this->command->error("Fichier non trouvé ! J'ai cherché dans : " . $file);
            return;
        }

        $this->command->info("Fichier trouvé ! Importation du plan comptable...");

        $data = file($file);
        $firstLine = true;
        $count = 0;

        foreach ($data as $line) {
            if ($firstLine) {
                $firstLine = false;
                continue;
            }

            // Utilisation du point-virgule (;) comme séparateur
            $row = str_getcsv($line, ';');

            if (isset($row[0]) && isset($row[1])) {
                DB::table('plan_comptables')->insert([
                    'compte'     => trim($row[0]),
                    'libelle'    => trim($row[1]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        $this->command->info("Succès : $count comptes importés avec succès.");
    }
}