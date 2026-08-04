<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liasse_table_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('societe_id')->constrained()->onDelete('cascade');
            $table->integer('exercice');
            $table->string('tableau_code');
            $table->string('status')->default('draft');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['societe_id', 'exercice', 'tableau_code'], 'liasse_table_validations_unique_table');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liasse_table_validations');
    }
};
