<?php

use App\Services\FiscalExerciseBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $backfill = app(FiscalExerciseBackfillService::class);
        $backfill->assertUnambiguous();

        Schema::create('fiscal_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('societe_id')->constrained()->cascadeOnDelete();
            $table->integer('exercice');
            $table->timestamps();

            $table->unique(['societe_id', 'exercice'], 'fiscal_exercises_societe_exercice_unique');
            $table->index(['user_id', 'societe_id', 'exercice'], 'fiscal_exercises_scope_index');
        });

        $backfill->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_exercises');
    }
};
