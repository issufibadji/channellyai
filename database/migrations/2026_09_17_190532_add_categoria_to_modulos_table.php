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
        Schema::table('modulos', function (Blueprint $table) {
            $table->enum('categoria', ['nivel', 'extra'])->default('nivel')->after('nome');
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->enum('nivel', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('categoria');
            $table->enum('nivel', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])->nullable(false)->change();
        });
    }
};
