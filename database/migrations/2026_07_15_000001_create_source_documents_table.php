<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('societe_id')->constrained()->onDelete('cascade');
            $table->integer('exercice');
            $table->string('document_type');
            $table->string('tableau_code');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status')->default('imported');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['societe_id', 'exercice'], 'source_documents_societe_exercice_idx');
            $table->index(['tableau_code', 'status'], 'source_documents_tableau_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_documents');
    }
};
