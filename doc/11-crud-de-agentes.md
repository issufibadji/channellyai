# 11 — CRUD de Agentes (múltiplos agentes por estabelecimento)

Antes desta fase, cada estabelecimento tinha exatamente **1** Agente, criado automaticamente na primeira vez que alguém abria "IA e Chatbot" — sem lista, sem criar outro, sem excluir.

Esta fase deixa o Agente de verdade com CRUD: um estabelecimento pode ter vários agentes, cada um com seus próprios dados de negócio, serviços e canal/bot conectado — por exemplo, um agente de agendamento e outro de suporte, cada um com seu próprio bot do Telegram.

---

## O que mudou

- **`atendimento/chatbot`** agora é a tela **"Agentes"** (`App\Livewire\Atendimento\AgenteManager`): lista os agentes do estabelecimento atual, cria um novo (só pede um nome) e exclui.
- **`atendimento/chatbot/{agente}`** é a tela de edição de um agente específico (a mesma `ChatbotManager` de antes — dados de negócio, serviços, canais, publicação, pareamento com o NanoClaw), agora recebendo o Agente pela rota em vez de pegar sempre o único do estabelecimento. Segue o mesmo padrão de revalidação de posse já usado em `AtendimentoShow` (o route-model-binding do Livewire roda antes do middleware `setEstabelecimento`, então `mount()` confere de novo `$agente->estabelecimento_id` contra o estabelecimento atual).
- **`canais.agente_id`** (nova coluna, nullable, `nullOnDelete`): cada canal (Telegram, WhatsApp, etc.) agora pertence a um Agente específico, não só ao estabelecimento. Isso é o que permite dois agentes do mesmo estabelecimento terem cada um seu próprio bot. Migration de dados (`2026_10_14_100001`) associou os canais já existentes ao único Agente que cada estabelecimento tinha até então, sem perder nada.
- **Excluir um Agente** apaga `AgenteDadosNegocio` e `AgenteServico` dele (cascade no banco), mas **não apaga** os canais conectados nem o histórico de atendimentos — só desvincula o canal (`agente_id` vira `null`), preservando as conversas já registradas.
- **`group_folder`**: como o valor-padrão vinha do slug do estabelecimento (que agora pode ter mais de um Agente), passou a receber um sufixo aleatório quando o slug já está em uso (a coluna continua única).
- **`RegistrarAtendimentoExterno`** (o serviço por trás do webhook do NanoClaw, `doc/08`) passou a criar/reaproveitar o canal por `agente_id` em vez de `estabelecimento_id` — coerente com o token do webhook já identificar o agente exato, não precisa mais adivinhar.

## Validado em produção (14/10/2026)

Criado um segundo agente ("Barbearia Ell Shaday") no mesmo estabelecimento da Barbearia Piloto, com:
- Dados de negócio próprios (endereço, horário, serviços, política — diferentes do primeiro agente).
- Bot do Telegram próprio (`@ellshaday_bot`, criado via `@BotFather`), conectado sem afetar o bot do primeiro agente.
- Conexão completa com o NanoClaw (criar agent group, gravar token, parear por código) — ver `doc/09-automacao-bots-telegram.md` pro relato completo desse teste.

## O que ficou de fora

- Reordenar/duplicar um agente (hoje só criar do zero e excluir).
- Qualquer limite de quantos agentes um estabelecimento pode ter (hoje ilimitado).
- UI pra mover um canal já conectado de um agente pro outro (hoje só dá pra desconectar de um e conectar de novo no outro, gerando um `@username` de bot diferente a cada vez, já que o Telegram amarra a credencial ao bot, não ao agente).
