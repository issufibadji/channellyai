<?php

namespace App\Models;

use App\Notifications\AulaAoVivoGravada;
use App\Notifications\AulaAoVivoIniciada;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AulaAoVivo extends Model
{
    use HasFactory;

    public const AGENDADA = 'agendada';

    public const AO_VIVO = 'ao_vivo';

    public const ENCERRADA = 'encerrada';

    protected $table = 'aulas_ao_vivo';

    protected $fillable = [
        'turma_id', 'conteudo_id', 'titulo', 'descricao', 'inicio_em', 'duracao_minutos',
        'sala', 'status', 'iniciada_em', 'encerrada_em',
    ];

    protected function casts(): array
    {
        return [
            'inicio_em' => 'datetime',
            'iniciada_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function conteudo(): BelongsTo
    {
        return $this->belongsTo(Conteudo::class);
    }

    /**
     * Aulas que ainda não terminaram (agendadas ou acontecendo agora).
     */
    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereIn('status', [self::AGENDADA, self::AO_VIVO]);
    }

    /**
     * Nome da sala no Jitsi. Aleatório e longo de propósito: a sala só é
     * revelada a quem o sistema autoriza (professor da turma e alunos matriculados).
     */
    public static function gerarSala(): string
    {
        return 'aula-'.Str::lower(Str::random(20));
    }

    public function estaAoVivo(): bool
    {
        return $this->status === self::AO_VIVO;
    }

    public function estaEncerrada(): bool
    {
        return $this->status === self::ENCERRADA;
    }

    /**
     * Abre a sala pros alunos e avisa a turma. Idempotente: não faz nada
     * se a aula já está ao vivo ou encerrada.
     */
    public function iniciar(): void
    {
        if ($this->status !== self::AGENDADA) {
            return;
        }

        $this->update(['status' => self::AO_VIVO, 'iniciada_em' => now()]);

        Notification::send($this->turma->alunos, new AulaAoVivoIniciada($this));
    }

    public function encerrar(): void
    {
        if ($this->status === self::ENCERRADA) {
            return;
        }

        $this->update(['status' => self::ENCERRADA, 'encerrada_em' => now()]);
    }

    /**
     * Publica a gravação como vídeo da turma (Google Drive, YouTube ou Vimeo,
     * o mesmo player das outras aulas em vídeo). Sem $modulo, cai no módulo
     * "Aulas Gravadas" — de atividades extras, então não conta no progresso do
     * curso. Republicar atualiza o mesmo conteúdo em vez de duplicar.
     */
    public function publicarGravacao(string $url, ?Modulo $modulo = null): Conteudo
    {
        $modulo ??= $this->turma->modulos()->firstOrCreate(
            ['nome' => 'Aulas Gravadas', 'categoria' => 'extra'],
            ['secao' => 'Aulas Gravadas', 'ordem' => 99],
        );

        $dados = [
            'modulo_id' => $modulo->id,
            'titulo' => $this->titulo,
            'tipo' => 'video',
            'url_externa' => $url,
        ];

        if ($this->conteudo) {
            $this->conteudo->update($dados);
            $conteudo = $this->conteudo;
        } else {
            $conteudo = Conteudo::create($dados + [
                'ordem' => ($modulo->conteudos()->max('ordem') ?? -1) + 1,
                'dias_liberacao' => 0,
                'bloqueado' => false,
            ]);

            $this->update(['conteudo_id' => $conteudo->id]);

            Notification::send($this->turma->alunos, new AulaAoVivoGravada($this));
        }

        return $conteudo;
    }

    /**
     * URL do iframe do Jitsi. O nome de exibição e as opções vão no fragmento
     * (#), então nunca chegam ao servidor do Jitsi como query. Aluno entra com
     * câmera e microfone desligados.
     */
    public function urlSala(User $usuario, bool $moderador): string
    {
        $dominio = config('services.jitsi.domain');

        $opcoes = [
            'userInfo.displayName' => rawurlencode(json_encode($usuario->name, JSON_UNESCAPED_UNICODE)),
            'config.subject' => rawurlencode(json_encode($this->titulo, JSON_UNESCAPED_UNICODE)),
            'config.prejoinConfig.enabled' => 'false',
            'config.prejoinPageEnabled' => 'false',
        ];

        if (! $moderador) {
            $opcoes['config.startWithAudioMuted'] = 'true';
            $opcoes['config.startWithVideoMuted'] = 'true';
        }

        $fragmento = collect($opcoes)->map(fn ($valor, $chave) => "{$chave}={$valor}")->implode('&');

        return "https://{$dominio}/{$this->sala}#{$fragmento}";
    }
}
