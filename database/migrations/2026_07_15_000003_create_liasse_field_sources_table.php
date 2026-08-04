<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liasse_field_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('societe_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_document_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('exercice');
            $table->string('tableau_code');
            $table->string('cle');
            $table->text('valeur')->nullable();
            $table->string('source_type')->default('manual');
            $table->string('status')->default('draft');
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['societe_id', 'exercice', 'tableau_code', 'cle'], 'liasse_field_sources_unique_field');
            $table->index(['source_type', 'status'], 'liasse_field_sources_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liasse_field_sources');
    }
};
