<?php

namespace App\Providers;

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
        //
    }
}
