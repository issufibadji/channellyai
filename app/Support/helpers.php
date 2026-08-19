<?php

use App\Services\AppConfigService;

if (! function_exists('config_app')) {
    function config_app(string $key, mixed $default = null): mixed
    {
        return app(AppConfigService::class)->get($key, $default);
    }
}

if (! function_exists('config_app_media')) {
    function config_app_media(string $key): ?string
    {
        return app(AppConfigService::class)->getMediaUrl($key);
    }
}
