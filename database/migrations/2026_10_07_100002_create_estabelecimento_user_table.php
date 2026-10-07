<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimento_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['estabelecimento_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimento_user');
    }
};
