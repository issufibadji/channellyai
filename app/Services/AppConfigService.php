<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppConfigService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(
            "app_config.{$key}",
            fn () => AppConfig::query()->where('key', $key)->value('value') ?? $default,
        );
    }

    public function getMediaUrl(string $key): ?string
    {
        return Cache::rememberForever(
            "app_config.{$key}.media",
            function () use ($key) {
                $path = AppConfig::query()->where('key', $key)->value('media_path');

                return $path ? Storage::disk('public')->url($path) : null;
            },
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
        Cache::forget("app_config.{$key}.media");
        Cache::forget('app_config.all');
    }
}
