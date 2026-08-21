<?php

use App\Models\Hydrometer;
use App\Models\Reading;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes unitários do Model Hydrometer.
 *
 * Validam scopes de query, relações e valores default do model,
 * isolados da camada HTTP.
 */
it('filtra hidrômetros por bairro usando scope', function () {
    Hydrometer::factory()->count(3)->create(['neighborhood' => 'Centro']);
    Hydrometer::factory()->count(2)->create(['neighborhood' => 'Bonfim']);

    $result = Hydrometer::byNeighborhood('Centro')->get();

    expect($result)->toHaveCount(3);
    expect($result->first()->neighborhood)->toBe('Centro');
});

it('filtra hidrômetros por status usando scope', function () {
    Hydrometer::factory()->count(4)->create(['status' => 'online']);
    Hydrometer::factory()->count(1)->create(['status' => 'offline']);

    $result = Hydrometer::byStatus('offline')->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->status)->toBe('offline');
});

it('possui relação hasMany com readings', function () {
    $hydrometer = Hydrometer::factory()->create();

    Reading::factory()->count(3)->create([
        'hydrometer_id' => $hydrometer->id,
    ]);

    expect($hydrometer->readings)->toHaveCount(3);
    expect($hydrometer->readings->first())->toBeInstanceOf(Reading::class);
});

it('permite criação com atribuição explícita de status', function () {
    $hydrometer = Hydrometer::create([
        'code' => 'HYD-DEFAULT-001',
        'latitude' => -17.1085,
        'longitude' => -43.8143,
        'address' => 'Rua Teste, 1',
        'neighborhood' => 'Centro',
        'type' => 'residential',
        'status' => 'online',
    ]);

    expect($hydrometer->status)->toBe('online');
    expect($hydrometer->code)->toBe('HYD-DEFAULT-001');

    $this->assertDatabaseHas('hydrometers', ['code' => 'HYD-DEFAULT-001']);
});
