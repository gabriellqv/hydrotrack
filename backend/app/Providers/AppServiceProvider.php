<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Provedor de serviços principal da aplicação.
 *
 * Responsável por registrar bindings no container IoC
 * e executar rotinas de inicialização (bootstrap) globais.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços da aplicação no container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa os serviços da aplicação após o registro.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Muitas tentativas de acesso. Aguarde um minuto e tente novamente.',
                ], 429);
            });
        });
    }
}
