<?php

use App\Models\Hydrometer;
use App\Services\HydrometerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes unitários do HydrometerService.
 *
 * Validam a lógica de negócio de listagem, criação, atualização e
 * exclusão de hidrômetros de forma isolada do Controller.
 */

beforeEach(function () {
    $this->service = new HydrometerService();
});

it('lista hidrômetros com paginação', function () {
    // Arrange: cria 20 hidrômetros no banco
    Hydrometer::factory()->count(20)->create();

    // Act: lista a primeira página com 15 por página (default)
    $result = $this->service->list();

    // Assert: deve retornar 15 na primeira página, com 20 no total
    expect($result)->toHaveCount(15);
    expect($result->total())->toBe(20);
});

it('filtra hidrômetros por bairro', function () {
    // Arrange
    Hydrometer::factory()->create(['neighborhood' => 'Centro']);
    Hydrometer::factory()->create(['neighborhood' => 'Bonfim']);
    Hydrometer::factory()->create(['neighborhood' => 'Centro']);

    // Act
    $result = $this->service->list(['neighborhood' => 'Centro']);

    // Assert
    expect($result->total())->toBe(2);
});

it('filtra hidrômetros por status', function () {
    Hydrometer::factory()->create(['status' => 'online']);
    Hydrometer::factory()->create(['status' => 'offline']);
    Hydrometer::factory()->create(['status' => 'alert']);

    $result = $this->service->list(['status' => 'offline']);

    expect($result->total())->toBe(1);
    expect($result->first()->status)->toBe('offline');
});

it('cria um hidrômetro com dados válidos', function () {
    $data = [
        'code' => 'HYD-TEST-001',
        'latitude' => -17.1085,
        'longitude' => -43.8143,
        'address' => 'Rua Teste, 123',
        'neighborhood' => 'Centro',
        'type' => 'residential',
    ];

    $hydrometer = $this->service->create($data);

    expect($hydrometer)->toBeInstanceOf(Hydrometer::class);
    expect($hydrometer->code)->toBe('HYD-TEST-001');
    expect($hydrometer->status)->toBe('online'); // default
});

it('exclui um hidrômetro e suas leituras em cascata', function () {
    $hydrometer = Hydrometer::factory()->create();
    $hydrometer->readings()->createMany([
        ['value_m3' => 5.0, 'reading_at' => now()],
        ['value_m3' => 3.2, 'reading_at' => now()->subDay()],
    ]);

    $this->service->delete($hydrometer);

    expect(Hydrometer::count())->toBe(0);
    expect(\App\Models\Reading::count())->toBe(0);
});
