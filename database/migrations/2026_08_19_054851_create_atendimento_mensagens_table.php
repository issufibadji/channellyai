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
        Schema::create('atendimento_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atendimento_id')->constrained('atendimentos')->cascadeOnDelete();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remetente');
            $table->text('conteudo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atendimento_mensagens');
    }
};
