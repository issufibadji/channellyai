<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('template')->nullable();
            $table->string('group_folder')->unique();
            $table->string('agent_group_id')->nullable();
            $table->string('status_publicacao')->default('rascunho');
            $table->timestamp('publicado_em')->nullable();
            $table->string('ultimo_commit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentes');
    }
};
