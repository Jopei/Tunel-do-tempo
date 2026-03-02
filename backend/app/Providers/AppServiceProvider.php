<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Atualizacoes\AtualizacaoRepositoryInterface;
use App\Repositories\Atualizacoes\AtualizacaoRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AtualizacaoRepositoryInterface::class,
            AtualizacaoRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
