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
        Schema::create('plan_comptables', function (Blueprint $table) {
            $table->id();
            // Le numéro de compte (ex: 6111), unique pour éviter les doublons
            $table->string('compte')->unique(); 
            
            // Le nom du compte (ex: Achats de marchandises)
            $table->string('libelle'); 
            
            // Bilan ou CPC (nullable au cas où certains comptes n'auraient pas de classe définie)
            $table->string('tableau')->nullable(); 
            
            // La ligne correspondante dans la liasse (nullable car remplie plus tard par l'expert)
            $table->string('ligne_liasse')->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_comptables');
    }
};