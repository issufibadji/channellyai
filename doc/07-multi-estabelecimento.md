# 07 — Multi-estabelecimento e Agente

Este documento descreve o modelo de dados, o isolamento e o fluxo de uso introduzidos na Fase 8.5 (ver `01-plano-implementacao.md`), que transformam o ChannellyAI de um painel single-tenant implícito em um painel multi-estabelecimento, com um ou mais Agentes de IA por estabelecimento.

---

## Modelo de dados

```
Estabelecimento
├── usuarios (N:N via estabelecimento_user)
├── clientes (1:N)
├── canais (1:N)
├── atendimentos (1:N)
└── agentes (1:N)
      ├── dadosNegocio (1:1, agente_dados_negocio)
      └── servicos (1:N, agente_servicos)
```

- **`estabelecimentos`**: `nome`, `slug` (único, imutável após criado), `tipo_negocio` (`barbearia`/`consultorio`/`consultoria`/`outro`), `ativo`.
- **`estabelecimento_user`**: pivot simples, define quais usuários enxergam qual estabelecimento.
- **`clientes`, `canais`, `atendimentos`**: ganharam `estabelecimento_id` (obrigatório, com FK). `atendimento_mensagens` não tem a coluna — herda o escopo via `atendimento_id`.
- **`agentes`**: `estabelecimento_id`, `nome`, `template`, `group_folder` (único — é o identificador compartilhado com o NanoClaw), `agent_group_id` (nullable, preenchido no futuro), `status_publicacao`, `publicado_em`, `ultimo_commit`.
- **`agente_dados_negocio`**: 1:1 com `agentes` — nome de exibição, tom de voz, endereço, horário, antecedência mínima para agendar, `calendar_id`, profissionais (texto livre), políticas (JSON, lista de textos), contato humano e mensagem de encaminhamento.
- **`agente_servicos`**: 1:N com `agentes` — nome, duração em minutos, preço.

A migração dos dados existentes (antes desta fase, o sistema era efetivamente single-tenant) foi feita em 3 passos, sem editar nenhuma migration antiga:
1. Adiciona `estabelecimento_id` nullable em `clientes`/`canais`/`atendimentos`.
2. Cria o estabelecimento padrão `consultorio-beta` e preenche `estabelecimento_id` em todas as linhas existentes.
3. Torna a coluna obrigatória, com FK e índice.

---

## Isolamento

- **Trait `App\Concerns\BelongsToEstabelecimento`**, aplicada em `Cliente`, `Canal`, `Atendimento`: registra um global scope que filtra toda consulta pelo estabelecimento atual, e preenche `estabelecimento_id` automaticamente na criação, se não informado.
- **`App\Services\CurrentEstabelecimento`**: singleton fino sobre a sessão (`current_estabelecimento_id`), usado pelo trait e pelos componentes.
- **Middleware `setEstabelecimento`** (`App\Http\Middleware\SetCurrentEstabelecimento`), aplicado nas rotas do módulo de Atendimento **depois** do `checkPermission` (para que a falta de permissão continue dando 403, e não um redirecionamento): admin nunca é forçado a escolher; usuário com exatamente 1 vínculo entra direto; usuário sem vínculo é redirecionado para uma tela informativa (`atendimento.sem-estabelecimento`).
- **Achado importante:** o route-model-binding do Livewire (ex.: `{atendimento}` → `Atendimento` em `AtendimentoShow`) é resolvido pelo Laravel **antes** do middleware de rota rodar (prioridade fixa do framework, `SubstituteBindings`). Por isso, `AtendimentoShow::mount()` faz uma segunda verificação explícita (`abort_unless($atendimento->estabelecimento_id === ...)`) em vez de confiar só no global scope durante o binding.
- **Seletor de estabelecimento** (`App\Livewire\EstabelecimentoSwitcher`, no topbar): lista os estabelecimentos do usuário (ou todos os ativos, se admin) e grava a escolha na sessão. Aparece mesmo quando só há 1 opção, se o usuário for admin (para que ele sempre possa confirmar/trocar).
- O grupo de menu que antes era o texto fixo "Atendimento" agora mostra o **nome do estabelecimento atual** (`App\Livewire\Sidebar`).

---

## Agente e o `negocio.md`

A tela "IA e Chatbot" deixou de ser um motor de regras por palavra-chave (removido nesta fase — `ChatbotEngine`, `ChatbotResponse`, `ChatbotRegra`) e passou a ser o formulário de configuração do Agente do estabelecimento atual:

- Ao abrir a tela, se o estabelecimento ainda não tem nenhum Agente, um é criado automaticamente (`group_folder` default = slug do estabelecimento).
- O formulário edita `AgenteDadosNegocio` e a lista de `AgenteServico` (adicionar/remover inline).
- O botão **"Pré-visualizar"** gera o Markdown a partir do **estado atual do formulário** (não precisa salvar antes) usando `App\Services\Agente\NegocioMarkdownGenerator::gerar()`, uma classe pura e testada isoladamente (`tests/Unit/Agente/NegocioMarkdownGeneratorTest.php`), incluindo os casos sem serviços e com acentos/aspas/barra vertical no conteúdo.
- O botão **"Salvar"** mostra a confirmação ("Dados do agente salvos com sucesso.") logo abaixo do formulário, perto dos botões, além da mensagem padrão no topo da página.

> **Atenção ao testar com múltiplas abas:** a tela "IA e Chatbot" é um componente Livewire de página inteira — cada aba do navegador mantém seu próprio estado do formulário desde que foi carregada. Se você editar/remover algo em uma aba e salvar, uma outra aba aberta antes dessa mudança ainda está com os dados antigos; salvar nela depois sobrescreve a alteração. Recarregue a página (F5) antes de confiar no que está vendo, ou evite manter duas abas na mesma tela de edição ao mesmo tempo.

### Publicação do agente (NanoClaw)

Contrato real confirmado no repositório de deploy do NanoClaw (`agente-atendimento-deploy`, GitHub `issufibadji/agente-atendimento-deploy`): publicar um agente é criar/atualizar dois arquivos nesse repositório, na branch configurada:

- `tenants/<group_folder>/negocio.md` — exatamente o markdown gerado por `NegocioMarkdownGenerator`.
- `tenants/<group_folder>/tenant.env` — `GROUP_FOLDER=<group_folder>\nAGENT_TEMPLATE=<template>\n`, gerado por `App\Services\Agente\TenantEnvGenerator`.

O push nesses arquivos dispara o GitHub Actions do próprio repositório de deploy, que faz `rsync` + restart do serviço na VPS — isso já existia e não foi alterado.

**Implementação**: `App\Services\Agente\GithubPublicadorDeAgente implements PublicadorDeAgente`, usando a API REST do GitHub (`PUT /repos/{repo}/contents/{path}`) via a facade `Http` — sem precisar de git/SSH no container do ChannellyAI. Configuração em `config/nanoclaw.php` / `.env`:

```
NANOCLAW_GITHUB_TOKEN=       # Personal Access Token com permissão de escrita no repo de deploy
NANOCLAW_GITHUB_REPO=issufibadji/agente-atendimento-deploy
NANOCLAW_GITHUB_BRANCH=main
```

Em caso de sucesso, atualiza `status_publicacao=publicado`, `publicado_em` e `ultimo_commit` (sha do commit retornado pela API). Em caso de falha, marca `status_publicacao=erro` e relança a exceção (a tela mostra uma mensagem genérica).

**Pré-requisito manual, fora do alcance desta integração**: o "agent group" (pasta `~/nanoclaw/groups/<group_folder>/`) precisa já existir na VPS antes da primeira publicação daquele slug — hoje só é criado à mão, via Claude Code na VPS (`./bin/ncl groups create --folder <slug> --name "..." --timezone America/Sao_Paulo`). O pipeline de deploy do NanoClaw **não cria** o agent group sozinho — só aplica a persona (`negocio.md`) a um agent group que já existe, e falha explicitamente se a pasta não existir. A tela "IA e Chatbot" mostra um lembrete desse passo antes do botão "Publicar".

**Não implementado ainda**: preenchimento de `agent_group_id` (esse identificador é interno do NanoClaw/`ncl`, só existe depois que alguém roda `ncl groups create` manualmente na VPS — não há como o ChannellyAI descobri-lo sozinho hoje); leitura de conversas/canais vindos do agente de volta para o ChannellyAI (fora do escopo definido no início do trabalho).

---

## Como criar um estabelecimento

1. Admin acessa **Administração do Sistema → Estabelecimentos**.
2. Preenche nome, slug (minúsculas/números/hífen, único — não pode ser alterado depois de criado) e tipo de negócio.
3. Vincula os usuários que devem enxergar esse estabelecimento (checkboxes no mesmo formulário).
4. Usuários com um único vínculo entram direto nele ao acessar o módulo de Atendimento; usuários sem nenhum vínculo veem a tela informativa.

## Como criar um agente

1. Dentro do estabelecimento desejado (selecionado no topbar), acesse **IA e Chatbot**.
2. Se for a primeira vez, o Agente já é criado automaticamente — basta preencher os dados do negócio e os serviços.
3. Use "Pré-visualizar" para conferir o `negocio.md` antes de salvar.
4. Depois de salvo, confirme que o agent group já existe na VPS (ver aviso na tela) e clique em "Publicar" para enviar `negocio.md`/`tenant.env` ao repositório de deploy do NanoClaw.

---

## O que ficou de fora

- Criação automática do agent group na VPS (`ncl groups create`) — continua manual. **Planejado em `doc/09-automacao-bots-telegram.md`**, junto com criar/editar/excluir o próprio bot do Telegram pelo ChannellyAI.
- Preenchimento de `agent_group_id` (depende do passo manual acima; mesmo plano).
- Leitura de conversas/canais vindos do agente de volta para o ChannellyAI — **feito** (ver `doc/08-webhook-nanoclaw.md`).
- Resposta do atendente no ChannellyAI chegando de volta ao cliente real (Telegram) — ainda não iniciado, sem plano escrito ainda.
