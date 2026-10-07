<?php

namespace App\Contracts;

use App\Models\Agente;

/**
 * Ponto de extensão para a publicação futura do agente no motor externo (NanoClaw).
 *
 * Nenhuma implementação existe ainda: esta etapa só gera e pré-visualiza o
 * negocio.md. A implementação futura fará o commit/push do markdown para o
 * group_folder do agente no NanoClaw e atualizará status_publicacao,
 * publicado_em e ultimo_commit.
 */
interface PublicadorDeAgente
{
    public function publicar(Agente $agente, string $markdown): void;
}
