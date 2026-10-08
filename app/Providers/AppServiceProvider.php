<?php

namespace App\Providers;

use App\Contracts\PublicadorDeAgente;
use App\Services\Agente\GithubPublicadorDeAgente;
use App\Services\CurrentEstabelecimento;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentEstabelecimento::class);
        $this->app->bind(PublicadorDeAgente::class, GithubPublicadorDeAgente::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
