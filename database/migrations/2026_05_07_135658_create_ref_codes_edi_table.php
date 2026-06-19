<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_codes_edi', function (Blueprint $table) {
            $table->id();
            $table->string('code_edi')->unique();
            $table->string('tableau')->nullable();
            $table->string('libelle')->nullable();
            $table->string('col1')->nullable();
            $table->string('col2')->nullable();
            $table->string('col3')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_codes_edi');
    }
};