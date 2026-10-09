<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agentes', function (Blueprint $table) {
            $table->string('pareamento_pairing_id')->nullable()->after('agent_group_id');
            $table->string('pareamento_status')->nullable()->after('pareamento_pairing_id');
            $table->string('pareamento_codigo')->nullable()->after('pareamento_status');
            $table->timestamp('pareado_em')->nullable()->after('pareamento_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('agentes', function (Blueprint $table) {
            $table->dropColumn(['pareamento_pairing_id', 'pareamento_status', 'pareamento_codigo', 'pareado_em']);
        });
    }
};
