<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * * Ce fichier est le "chef d'orchestre" qui remplit ta base de données.
     * Il respecte l'ordre chronologique des dépendances (Clés étrangères).
     */
    public function run(): void
    {
        $this->call([
            // 1. Crée ton compte utilisateur Maria
            UserSeeder::class,
            
            // 2. Crée la société de test liée à ton compte (Indispensable pour lier la liasse)
            SocieteSeeder::class,
            
            // 3. Importe les 1062 comptes officiels du plan comptable marocain
            PlanComptableSeeder::class,
            
            // 4. Importe les 1746 codes EDI de la DGI depuis le fichier import_edi.csv
            RefCodesEdiSeeder::class,
        ]);
    }
}