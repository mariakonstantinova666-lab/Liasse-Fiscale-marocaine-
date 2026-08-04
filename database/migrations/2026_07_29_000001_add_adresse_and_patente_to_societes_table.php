<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('societes', function (Blueprint $table) {
            $table->string('patente', 50)->nullable()->after('cnss');
            $table->text('adresse')->nullable()->after('patente');
        });
    }

    public function down(): void
    {
        Schema::table('societes', function (Blueprint $table) {
            $table->dropColumn(['patente', 'adresse']);
        });
    }
};
