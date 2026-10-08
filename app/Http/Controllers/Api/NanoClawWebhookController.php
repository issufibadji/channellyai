<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agente;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\AtendimentoMensagem;
use App\Models\Atendimento\Canal;
use App\Services\Atendimento\RegistrarAtendimentoExterno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NanoClawWebhookController extends Controller
{
    public function store(Request $request, RegistrarAtendimentoExterno $service): JsonResponse
    {
        $agente = Agente::where('webhook_token', (string) $request->header('X-NanoClaw-Token'))->first();

        abort_unless($agente, 401);

        $data = $request->validate([
            'cliente.nome' => 'required|string|max:255',
            'cliente.telefone' => 'nullable|string|max:30',
            'canal' => 'required|string|in:'.implode(',', array_keys(Canal::TIPOS)),
            'status' => 'nullable|string|in:'.implode(',', array_keys(Atendimento::STATUSES)),
            'resumo' => 'nullable|string',
            'mensagens' => 'required|array|min:1',
            'mensagens.*.remetente' => 'required|string|in:'.implode(',', AtendimentoMensagem::REMETENTES),
            'mensagens.*.conteudo' => 'required|string',
        ]);

        $atendimento = $service->registrar(
            agente: $agente,
            cliente: $data['cliente'],
            canal: $data['canal'],
            resumo: $data['resumo'] ?? null,
            status: $data['status'] ?? null,
            mensagens: $data['mensagens'],
        );

        return response()->json(['atendimento_id' => $atendimento->id], 201);
    }
}
