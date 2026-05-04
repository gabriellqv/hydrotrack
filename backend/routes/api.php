<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HydrometerController;
use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (sem autenticação)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Endpoint de ingestão M2M (autenticado via API key, não via Sanctum)
Route::post('/ingest', [IngestController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (requerem token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Hidrômetros (CRUD)
    Route::apiResource('hydrometers', HydrometerController::class)->only(['index', 'show']);
    Route::apiResource('hydrometers', HydrometerController::class)->only(['store', 'update', 'destroy'])->middleware('admin');

    // Leituras de um hidrômetro específico
    Route::get('/hydrometers/{hydrometer}/readings', [HydrometerController::class, 'readings']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/consumption', [DashboardController::class, 'consumption']);
        Route::get('/map', [DashboardController::class, 'map']);
    });

    // Alertas
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
});
