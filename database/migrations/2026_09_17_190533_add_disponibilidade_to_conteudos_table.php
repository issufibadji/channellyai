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
            $table->integer('dias_liberacao')->default(0)->after('ordem');
            $table->boolean('bloqueado')->default(false)->after('dias_liberacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->dropColumn(['dias_liberacao', 'bloqueado']);
        });
    }
};
