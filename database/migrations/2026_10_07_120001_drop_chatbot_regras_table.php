<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chatbot_regras');
    }

    public function down(): void
    {
        Schema::create('chatbot_regras', function ($table) {
            $table->id();
            $table->string('gatilho');
            $table->text('resposta')->nullable();
            $table->string('setor_transferencia')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }
};
