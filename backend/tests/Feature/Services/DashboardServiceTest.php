<?php

use App\Models\Hydrometer;
use App\Models\Reading;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Testes do DashboardService.
 *
 * Cobrem a logica de cache e agregacao de dados para o dashboard.
 */
beforeEach(function () {
    $this->service = new DashboardService;
});

it('retorna resumo do dashboard agregado corretamente', function () {
    Hydrometer::factory()->count(3)->create(['status' => 'online']);
    Hydrometer::factory()->count(2)->create(['status' => 'offline']);
    $hydrometer = Hydrometer::factory()->create(['status' => 'alert']);

    Reading::factory()->count(2)->create([
        'hydrometer_id' => $hydrometer->id,
        'reading_at' => now(),
    ]);
    Reading::factory()->count(1)->create([
        'hydrometer_id' => $hydrometer->id,
        'reading_at' => now()->subDay(),
    ]);

    $summary = $this->service->getSummary();

    expect($summary)->toHaveKey('total_hydrometers', 6);
    expect($summary)->toHaveKey('online', 3);
    expect($summary)->toHaveKey('offline', 2);
    expect($summary)->toHaveKey('alert', 1);
    expect($summary)->toHaveKey('total_readings_today', 2);
    expect($summary)->toHaveKey('pending_alerts', 0);
});

it('cacheia o resumo do dashboard', function () {
    Cache::spy();

    $this->service->getSummary();

    Cache::shouldHaveReceived('remember')->with('dashboard:summary', 60, Mockery::type('Closure'));
});

it('retorna grafico de consumo agregado por dia', function () {
    $hydrometer = Hydrometer::factory()->create();

    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 10.0,
        'reading_at' => now()->subDays(2),
    ]);

    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 5.0,
        'reading_at' => now()->subDays(2),
    ]);

    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 3.0,
        'reading_at' => now()->subDays(10),
    ]);

    $chart = $this->service->getConsumptionChart(7);

    expect($chart)->toHaveCount(1);
    expect((float) $chart[0]['total_m3'])->toBe(15.0);
});

it('ignora leituras fora do periodo do grafico de consumo', function () {
    $hydrometer = Hydrometer::factory()->create();

    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 1.0,
        'reading_at' => now()->subDays(31),
    ]);

    $chart = $this->service->getConsumptionChart(30);

    expect($chart)->toHaveCount(0);
});

it('cacheia grafico de consumo com chave parametrizada', function () {
    Cache::spy();

    $this->service->getConsumptionChart(7);

    Cache::shouldHaveReceived('remember')->with('dashboard:consumption:7', 60, Mockery::type('Closure'));
});

it('invalida todos os caches do dashboard', function () {
    Cache::spy();

    DashboardService::invalidateCache();

    Cache::shouldHaveReceived('forget')->with('dashboard:summary');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:7');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:30');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:90');
    Cache::shouldHaveReceived('forget')->with('dashboard:map');
});

it('retorna chave de cache padronizada para consumo', function () {
    expect($this->service->getConsumptionCacheKey(30))->toBe('dashboard:consumption:30');
});

it('retorna periodos de consumo suportados', function () {
    expect(DashboardService::getConsumptionPeriods())->toBe([7, 30, 90]);
});
