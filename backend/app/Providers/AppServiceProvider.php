<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Historia;
use App\Policies\HistoriaPolicy;
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
        Gate::policy(Historia::class, HistoriaPolicy::class);

        // Login: limite por IP + e-mail (429 padrao do Laravel)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by('login:' . $request->ip() . '|' . strtolower((string) $request->input('login')));
        });

        // Esqueci/redefinir senha: limite por IP e por e-mail
        RateLimiter::for('senha', function (Request $request) {
            return [
                Limit::perMinute(5)->by('ip:' . $request->ip()),
                Limit::perHour(10)->by('email:' . strtolower((string) $request->input('email'))),
            ];
        });
    }
}
