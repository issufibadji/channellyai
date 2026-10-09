<?php

return [
    'github_token' => env('NANOCLAW_GITHUB_TOKEN'),
    'github_repo' => env('NANOCLAW_GITHUB_REPO', 'issufibadji/agente-atendimento-deploy'),
    'branch' => env('NANOCLAW_GITHUB_BRANCH', 'main'),

    'control_url' => env('NANOCLAW_CONTROL_URL'),
    'control_token' => env('NANOCLAW_CONTROL_TOKEN'),
];
