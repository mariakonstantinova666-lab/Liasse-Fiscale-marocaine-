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
        Schema::create('compte_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('racine_compte', 10); // ex: 6111
            $table->string('libelle');           // ex: Achats de marchandises
            $table->string('tableau_liasse');   // ex: CPC, BILAN_ACTIF
            $table->string('code_edi');         // Le code DGI (ex: 102)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compte_mappings');
    }
};