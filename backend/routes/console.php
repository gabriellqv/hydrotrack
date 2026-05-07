<?php

/**
 * Comandos Artisan personalizados e agendamentos da aplicação.
 *
 * Registra comandos CLI disponíveis via `php artisan` e define
 * a agenda de execução automática do Scheduler.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Exibe uma citação inspiradora');

/*
|--------------------------------------------------------------------------
| Agendamento de Jobs (Laravel Scheduler)
|--------------------------------------------------------------------------
|
| O Watchdog roda a cada 5 minutos para detectar hidrômetros silenciosos.
| Em produção, basta configurar o cron: * * * * * php artisan schedule:run
|
*/
Schedule::command('hydrotrack:watchdog')->everyFiveMinutes();
