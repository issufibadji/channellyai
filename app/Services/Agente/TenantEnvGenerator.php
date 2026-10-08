<?php

namespace App\Services\Agente;

use App\Models\Agente;

class TenantEnvGenerator
{
    public function gerar(Agente $agente): string
    {
        return "GROUP_FOLDER={$agente->group_folder}\nAGENT_TEMPLATE={$agente->template}\n";
    }
}
