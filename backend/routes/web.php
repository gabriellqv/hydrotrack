<?php

/**
 * Rotas web da aplicação HydroTrack.
 *
 * Define as rotas acessíveis via navegador (sessão + CSRF).
 * Rotas de API devem ser registradas em api.php.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'HydroTrack API',
        'status' => 'running',
    ]);
});
