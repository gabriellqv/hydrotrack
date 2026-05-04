<?php

/**
 * Comandos Artisan personalizados da aplicação.
 *
 * Registra comandos CLI disponíveis via `php artisan`.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Exibe uma citação inspiradora');
