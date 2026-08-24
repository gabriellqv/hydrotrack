<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HydrometerController;
use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (sem autenticação)
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'check']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Endpoint de ingestão M2M (autenticado via API key no header X-API-Key)
Route::post('/ingest', [IngestController::class, 'store'])->middleware(['ingest.auth', 'throttle:60,1']);

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (requerem token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Hidrômetros (CRUD)
    Route::apiResource('hydrometers', HydrometerController::class)->only(['index', 'show']);
    Route::apiResource('hydrometers', HydrometerController::class)->only(['store', 'update', 'destroy'])->middleware('admin');

    // Leituras de um hidrômetro específico
    Route::get('/hydrometers/{hydrometer}/readings', [HydrometerController::class, 'readings']);
    Route::get('/hydrometers/{hydrometer}/readings/export', [HydrometerController::class, 'readingsExport']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/consumption', [DashboardController::class, 'consumption']);
        Route::get('/map', [DashboardController::class, 'map']);
    });

    // Alertas
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->middleware('admin');
});
