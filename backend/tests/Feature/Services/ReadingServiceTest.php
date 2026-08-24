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

it('gera alerta de leitura zerada quando value_m3 e igual a 0', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.0,
        'reading_at' => now()->toISOString(),
    ]);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('alert');

    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'zero_reading',
        'resolved' => false,
    ]);
});

it('prioriza alerta de leitura zerada sobre alto consumo quando value_m3 e 0', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.0,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 1);
    $this->assertDatabaseMissing('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
    ]);
});

it('nao gera alerta de leitura zerada para valores proximos de zero', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.001,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 0);

    $hydrometer->refresh();
    expect($hydrometer->status)->toBe('online');
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

it('nao duplica alerta de leitura zerada para o mesmo hidrometro', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    foreach ([0.0, 0.0] as $value) {
        $this->service->ingest([
            'hydrometer_code' => $hydrometer->code,
            'value_m3' => $value,
            'reading_at' => now()->toISOString(),
        ]);
    }

    $this->assertDatabaseCount('alerts', 1);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'zero_reading',
        'resolved' => false,
    ]);
});

it('nao duplica alerta de alto consumo para o mesmo hidrometro', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    foreach ([15.5, 20.0] as $value) {
        $this->service->ingest([
            'hydrometer_code' => $hydrometer->code,
            'value_m3' => $value,
            'reading_at' => now()->toISOString(),
        ]);
    }

    $this->assertDatabaseCount('alerts', 1);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
        'resolved' => false,
    ]);
});

it('cria novo alerta zerado apos resolver o anterior', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.0,
        'reading_at' => now()->toISOString(),
    ]);

    $firstAlert = Alert::where('hydrometer_id', $hydrometer->id)
        ->where('type', 'zero_reading')
        ->first();

    $firstAlert->update(['resolved' => true, 'resolved_at' => now()]);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.0,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 2);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'zero_reading',
        'resolved' => false,
    ]);
});

it('cria novo alerta de alto consumo apos resolver o anterior', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 15.5,
        'reading_at' => now()->toISOString(),
    ]);

    $firstAlert = Alert::where('hydrometer_id', $hydrometer->id)
        ->where('type', 'high_consumption')
        ->first();

    $firstAlert->update(['resolved' => true, 'resolved_at' => now()]);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 20.0,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 2);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
        'resolved' => false,
    ]);
});

it('gera dois alertas distintos para leitura zerada e alto consumo', function () {
    $hydrometer = Hydrometer::factory()->create(['status' => 'online']);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 0.0,
        'reading_at' => now()->toISOString(),
    ]);

    $this->service->ingest([
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 15.5,
        'reading_at' => now()->toISOString(),
    ]);

    $this->assertDatabaseCount('alerts', 2);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'zero_reading',
    ]);
    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
    ]);
});
