<?php

/*
| Só o que sobrescrevemos do padrão do Livewire (o resto vem do config do vendor).
|
| O teto padrão de upload temporário do Livewire é 12MB e roda ANTES da validação
| do componente — sem isto, um `max:20480` no componente nunca deixaria passar
| arquivo entre 12 e 20MB. 51200 KB (50MB) cobre PDF (20MB) e vídeo curto (50MB);
| o limite fino de cada tipo continua na validação do componente.
|
| Atenção: mergeConfigFrom é raso, então a chave temporary_file_upload precisa
| ser repetida por inteiro.
*/

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:51200'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
