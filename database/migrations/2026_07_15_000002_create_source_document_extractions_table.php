<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_document_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_document_id')->constrained()->onDelete('cascade');
            $table->json('raw_data')->nullable();
            $table->json('mapped_data')->nullable();
            $table->json('errors')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['source_document_id', 'status'], 'source_doc_extractions_document_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_document_extractions');
    }
};
