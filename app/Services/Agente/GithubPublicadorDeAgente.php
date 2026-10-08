<?php

namespace App\Services\Agente;

use App\Contracts\PublicadorDeAgente;
use App\Models\Agente;
use Illuminate\Support\Facades\Http;

class GithubPublicadorDeAgente implements PublicadorDeAgente
{
    public function __construct(
        private readonly TenantEnvGenerator $tenantEnvGenerator,
    ) {}

    public function publicar(Agente $agente, string $markdown): void
    {
        $tenantEnv = $this->tenantEnvGenerator->gerar($agente);

        try {
            $this->putFile(
                "tenants/{$agente->group_folder}/negocio.md",
                $markdown,
                "Atualiza negocio.md do agente {$agente->group_folder} via ChannellyAI",
            );

            $commit = $this->putFile(
                "tenants/{$agente->group_folder}/tenant.env",
                $tenantEnv,
                "Atualiza tenant.env do agente {$agente->group_folder} via ChannellyAI",
            );
        } catch (\Throwable $e) {
            $agente->update(['status_publicacao' => 'erro']);

            throw $e;
        }

        $agente->update([
            'status_publicacao' => 'publicado',
            'publicado_em' => now(),
            'ultimo_commit' => $commit,
        ]);
    }

    private function putFile(string $path, string $conteudo, string $mensagem): string
    {
        $repo = config('nanoclaw.github_repo');
        $branch = config('nanoclaw.branch');

        $atual = Http::withToken(config('nanoclaw.github_token'))
            ->get("https://api.github.com/repos/{$repo}/contents/{$path}", ['ref' => $branch]);

        $sha = $atual->successful() ? $atual->json('sha') : null;

        $resposta = Http::withToken(config('nanoclaw.github_token'))
            ->put("https://api.github.com/repos/{$repo}/contents/{$path}", array_filter([
                'message' => $mensagem,
                'content' => base64_encode($conteudo),
                'branch' => $branch,
                'sha' => $sha,
            ]));

        if ($resposta->failed()) {
            throw new \RuntimeException("Falha ao publicar {$path} no repositório do NanoClaw: {$resposta->status()}");
        }

        return $resposta->json('commit.sha');
    }
}
