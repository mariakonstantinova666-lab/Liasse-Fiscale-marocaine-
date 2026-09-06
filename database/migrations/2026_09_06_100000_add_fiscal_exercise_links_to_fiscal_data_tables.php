<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liasse_data', function (Blueprint $table) {
            $table->foreignId('societe_id')->nullable()->after('user_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('fiscal_exercise_id')->nullable()->after('societe_id')
                ->constrained()->restrictOnDelete();
        });

        Schema::table('source_documents', function (Blueprint $table) {
            $table->foreignId('fiscal_exercise_id')->nullable()->after('societe_id')
                ->constrained()->restrictOnDelete();
        });

        Schema::table('liasse_field_sources', function (Blueprint $table) {
            $table->foreignId('fiscal_exercise_id')->nullable()->after('societe_id')
                ->constrained()->restrictOnDelete();
        });

        Schema::table('liasse_table_validations', function (Blueprint $table) {
            $table->foreignId('fiscal_exercise_id')->nullable()->after('societe_id')
                ->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('liasse_table_validations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_exercise_id');
        });

        Schema::table('liasse_field_sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_exercise_id');
        });

        Schema::table('source_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_exercise_id');
        });

        Schema::table('liasse_data', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_exercise_id');
            $table->dropConstrainedForeignId('societe_id');
        });
    }
};
