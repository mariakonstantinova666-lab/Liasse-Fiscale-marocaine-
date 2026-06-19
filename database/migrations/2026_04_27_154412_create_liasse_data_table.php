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
        Schema::create('liasse_data', function (Blueprint $table) {
            $table->id();
            // Lie la donnée à l'utilisateur qui l'a saisie
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // L'année concernée (ex: 2026)
            $table->integer('exercice');
            // Le numéro du tableau (ex: T4, T15, T20)
            $table->string('tableau_code'); 
            // L'identifiant de la ligne ou du champ (ex: 'reintegration_amendes')
            $table->string('cle');          
            // La valeur saisie (on utilise text car certaines notes peuvent être longues)
            $table->text('valeur')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liasse_data');
    }
};