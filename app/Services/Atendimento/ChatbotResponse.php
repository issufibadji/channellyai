<?php

namespace App\Services\Atendimento;

class ChatbotResponse
{
    public function __construct(
        public readonly string $resposta,
        public readonly ?string $setorTransferencia = null,
    ) {}
}
