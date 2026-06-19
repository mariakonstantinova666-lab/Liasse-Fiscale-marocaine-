<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('balance_items', function (Blueprint $table) {
            $table->id();
            
            // On lie la ligne à l'utilisateur qui l'a importée
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            // NOUVEAU : On lie la ligne à la société concernée (Obligatoire pour la liasse)
            $table->foreignId('societe_id')->constrained()->onDelete('cascade');
            
            // Informations comptables de la balance
            $table->string('compte'); 
            $table->string('libelle');
            $table->decimal('solde_debiteur', 15, 2)->default(0);
            $table->decimal('solde_crediteur', 15, 2)->default(0);
            $table->integer('exercice'); // L'année (ex: 2026)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_items');
    }
};