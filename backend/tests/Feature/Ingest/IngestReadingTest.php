<?php

use App\Models\Hydrometer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint POST /api/ingest.
 *
 * Validam o fluxo de ingestão M2M: autenticação via API key,
 * criação de leitura, atualização do hidrômetro e rejeição
 * de requisições sem autenticação ou com dados inválidos.
 */
it('registra leitura com API key válida', function () {
    config(['services.ingest.api_key' => 'test-api-key-123']);

    $hydrometer = Hydrometer::factory()->create(['code' => 'HYD-INGEST-001']);

    $response = $this->postJson('/api/ingest', [
        'hydrometer_code' => 'HYD-INGEST-001',
        'value_m3' => 5.75,
        'reading_at' => '2026-05-05 12:00:00',
    ], [
        'X-API-Key' => 'test-api-key-123',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('readings', [
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 5.75,
    ]);

    expect($hydrometer->fresh()->status)->toBe('online');
});

it('rejeita ingestão sem header X-API-Key', function () {
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->postJson('/api/ingest', [
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => 3.0,
        'reading_at' => '2026-05-05 12:00:00',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'API key inválida ou ausente.');
});

it('rejeita ingestão com API key inválida', function () {
    config(['services.ingest.api_key' => 'chave-correta']);

    $response = $this->postJson('/api/ingest', [
        'hydrometer_code' => 'HYD-001',
        'value_m3' => 3.0,
        'reading_at' => '2026-05-05 12:00:00',
    ], [
        'X-API-Key' => 'chave-errada',
    ]);

    $response->assertStatus(401);
});

it('rejeita ingestão com código de hidrômetro inexistente', function () {
    config(['services.ingest.api_key' => 'test-api-key-123']);

    $response = $this->postJson('/api/ingest', [
        'hydrometer_code' => 'HYD-FANTASMA',
        'value_m3' => 3.0,
        'reading_at' => '2026-05-05 12:00:00',
    ], [
        'X-API-Key' => 'test-api-key-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('hydrometer_code');
});

it('rejeita ingestão com valor de consumo negativo', function () {
    config(['services.ingest.api_key' => 'test-api-key-123']);

    $hydrometer = Hydrometer::factory()->create();

    $response = $this->postJson('/api/ingest', [
        'hydrometer_code' => $hydrometer->code,
        'value_m3' => -5.0,
        'reading_at' => '2026-05-05 12:00:00',
    ], [
        'X-API-Key' => 'test-api-key-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('value_m3');
});
