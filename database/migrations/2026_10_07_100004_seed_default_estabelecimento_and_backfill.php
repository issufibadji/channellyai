<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('estabelecimentos')->insertGetId([
            'nome' => 'Consultório Beta',
            'slug' => 'consultorio-beta',
            'tipo_negocio' => 'consultorio',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['clientes', 'canais', 'atendimentos'] as $tabela) {
            DB::table($tabela)->whereNull('estabelecimento_id')->update(['estabelecimento_id' => $id]);
        }
    }

    public function down(): void
    {
        DB::table('estabelecimentos')->where('slug', 'consultorio-beta')->delete();
    }
};
