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
        Schema::table('conteudos', function (Blueprint $table) {
            $table->enum('tipo', ['video', 'video_curto', 'pdf', 'texto', 'exercicio', 'link'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->enum('tipo', ['video', 'pdf', 'texto', 'exercicio', 'link'])->change();
        });
    }
};
