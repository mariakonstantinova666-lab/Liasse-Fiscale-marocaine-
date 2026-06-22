<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historisation N / N-1.
     *
     * Le modèle est volontairement conservé : une balance N-1 = des lignes
     * balance_items portant le même societe_id mais exercice = (année - 1).
     * Aucune colonne n'est ajoutée ni supprimée (migration non destructive) :
     * on se contente d'indexer les colonnes utilisées par TOUTES les lectures
     * (balances N et N-1, alimentation de la colonne "Exercice Précédent").
     */
    public function up(): void
    {
        Schema::table('balance_items', function (Blueprint $table) {
            $table->index(['societe_id', 'exercice'], 'balance_items_societe_exercice_idx');
            $table->index(['user_id', 'exercice'], 'balance_items_user_exercice_idx');
        });
    }

    public function down(): void
    {
        Schema::table('balance_items', function (Blueprint $table) {
            $table->dropIndex('balance_items_societe_exercice_idx');
            $table->dropIndex('balance_items_user_exercice_idx');
        });
    }
};
