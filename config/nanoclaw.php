<?php

return [
    'github_token' => env('NANOCLAW_GITHUB_TOKEN'),
    'github_repo' => env('NANOCLAW_GITHUB_REPO', 'issufibadji/agente-atendimento-deploy'),
    'branch' => env('NANOCLAW_GITHUB_BRANCH', 'main'),
    'webhook_secret' => env('NANOCLAW_WEBHOOK_SECRET'),
];
