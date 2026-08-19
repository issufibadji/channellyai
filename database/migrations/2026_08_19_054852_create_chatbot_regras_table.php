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
        Schema::create('chatbot_regras', function (Blueprint $table) {
            $table->id();
            $table->string('gatilho');
            $table->text('resposta')->nullable();
            $table->string('setor_transferencia')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_regras');
    }
};
