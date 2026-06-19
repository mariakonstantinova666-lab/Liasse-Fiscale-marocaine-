<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompteMapping;

class CompteMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            // --- TABLEAU DU CPC (Compte de Produits et Charges) ---
            // Les racines de comptes correspondent au Plan Comptable Marocain
            ['racine_compte' => '711', 'libelle' => 'Ventes de marchandises', 'tableau_liasse' => 'CPC', 'code_edi' => '1'],
            ['racine_compte' => '712', 'libelle' => 'Ventes de biens et services produits', 'tableau_liasse' => 'CPC', 'code_edi' => '2'],
            ['racine_compte' => '611', 'libelle' => 'Achats de marchandises', 'tableau_liasse' => 'CPC', 'code_edi' => '11'],
            ['racine_compte' => '612', 'libelle' => 'Achats consommés de matières et fournitures', 'tableau_liasse' => 'CPC', 'code_edi' => '12'],
            ['racine_compte' => '617', 'libelle' => 'Charges de personnel', 'tableau_liasse' => 'CPC', 'code_edi' => '17'],
            
            // --- TABLEAU DU BILAN (ACTIF) ---
            ['racine_compte' => '211', 'libelle' => 'Frais préliminaires', 'tableau_liasse' => 'BILAN_ACTIF', 'code_edi' => '10'],
            ['racine_compte' => '231', 'libelle' => 'Terrains', 'tableau_liasse' => 'BILAN_ACTIF', 'code_edi' => '20'],
            ['racine_compte' => '232', 'libelle' => 'Constructions', 'tableau_liasse' => 'BILAN_ACTIF', 'code_edi' => '21'],
            ['racine_compte' => '311', 'libelle' => 'Marchandises (Stocks)', 'tableau_liasse' => 'BILAN_ACTIF', 'code_edi' => '30'],
            ['racine_compte' => '514', 'libelle' => 'Banque', 'tableau_liasse' => 'BILAN_ACTIF', 'code_edi' => '40'],
            
            // --- TABLEAU DU BILAN (PASSIF) ---
            ['racine_compte' => '111', 'libelle' => 'Capital social', 'tableau_liasse' => 'BILAN_PASSIF', 'code_edi' => '101'],
            ['racine_compte' => '119', 'libelle' => 'Report à nouveau', 'tableau_liasse' => 'BILAN_PASSIF', 'code_edi' => '105'],
            ['racine_compte' => '441', 'libelle' => 'Fournisseurs et comptes rattachés', 'tableau_liasse' => 'BILAN_PASSIF', 'code_edi' => '120'],
            ['racine_compte' => '442', 'libelle' => 'Clients créditeurs, avances et acomptes', 'tableau_liasse' => 'BILAN_PASSIF', 'code_edi' => '121'],
        ];

        foreach ($mappings as $data) {
            CompteMapping::create($data);
        }
    }
}