<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_exercice_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('societe_id')->constrained()->cascadeOnDelete();
            $table->integer('exercice');
            $table->timestamps();
            $table->unique(['user_id', 'societe_id'], 'active_exercice_preferences_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_exercice_preferences');
    }
};
