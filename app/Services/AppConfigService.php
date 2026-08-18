<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppConfigService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(
            "app_config.{$key}",
            fn () => AppConfig::query()->where('key', $key)->value('value') ?? $default,
        );
    }

    public function all(): Collection
    {
        return Cache::rememberForever(
            'app_config.all',
            fn () => AppConfig::query()->orderBy('key')->get(),
        );
    }

    public function forget(string $key): void
    {
        Cache::forget("app_config.{$key}");
        Cache::forget('app_config.all');
    }
}
