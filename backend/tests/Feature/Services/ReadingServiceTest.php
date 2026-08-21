<?php

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\Reading;
use App\Services\ReadingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Testes do ReadingService.
 *
 * Cobrem a regra de negocio mais critica do sistema: ingestao de leituras,
 * deteccao de alto consumo, transicao de status e geracao de alertas.
 */
beforeEach(function () {
    $this->service = new ReadingService;
});

it('persiste uma leitura e atualiza o hidrometro para online', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'offline']);

    $reading = $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 5.0,
        'reading_at' => now()->toISOString(),
    ]);

    expect($reading)->toBeInstanceOf(Reading::class);
    expect($reading->hydrometer_id)->toBe($hydrometer->id);
    expect((float) $reading->value_m3)->toBe(5.0);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('online');
    expect($hydrometer->last_reading_at)->not->toBeNull();
});

it('gera alerta de alto consumo quando value_m3 e maior que 10', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 15.5,
        'reading_at' => now()->toISOString(),
    ]);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('alert');

    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
        'resolved' => false,
    ]);
});

it('nao gera alerta quando value_m3 e exatamente 10', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 10.0,
        'reading_at' => now()->toISOString(),
    ]);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('online');

    $this->assertDatabaseCount('alerts', 0);
});

it('nao gera alerta quando value_m3 e menor que 10', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 9.999,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 0);
});

it('lanca excecao quando codigo de hidrometro nao existe', function () {
    expect(fn () => $this->service->ingest([
        'hydrometer_code' => 'HYD-FANTASMA',
        'value_m3' => 3.0,
        'reading_at' => now()->toISOString(),
    ]))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('readings', 0);
});

it('invalida cache do dashboard apos ingestao', function () {
    Cache::spy();

    $hydrometer = Hydrometer::factory()->create();

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 1.0,
        'reading_at' => now()->toISOString(),
    ]);

    Cache::shouldHaveReceived('forget')->with('dashboard:summary');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:7');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:30');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:90');
    Cache::shouldHaveReceived('forget')->with('dashboard:map');
});

it('garante atomicidade: falha no alerta nao persiste leitura parcial', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'offline']);

    // Simula uma falha durante a transacao ao interceptar Alert::create
    Alert::saving(fn () => throw new RuntimeException('Falha simulada'));

    try {
        $this->service->ingest([
            'hydrometer_code' => $hydrometer->code,
            'value_m3' => 20.0,
            'reading_at' => now()->toISOString(),
        ]);
    } catch (RuntimeException $e) {
        // excecao esperada
    }

    $this->assertDatabaseCount('readings', 0);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('offline');
});
