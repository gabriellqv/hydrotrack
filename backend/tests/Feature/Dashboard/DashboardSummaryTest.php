<?php

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes do endpoint GET /api/dashboard/summary.
 *
 * Validam que os totais retornados refletem fielmente o estado atual
 * do banco de dados, incluindo contagens por status e alertas pendentes.
 */
it('retorna o resumo correto do dashboard', function () {
    $user = User::factory()->create();

    // Arrange: cenário controlado
    Hydrometer::factory()->count(5)->create(['status' => 'online']);
    Hydrometer::factory()->count(2)->create(['status' => 'offline']);
    Hydrometer::factory()->count(1)->create(['status' => 'alert']);

    $hydrometer = Hydrometer::first();
    Reading::factory()->count(3)->create([
        'hydrometer_id' => $hydrometer->id,
        'reading_at' => now(),
    ]);

    Alert::factory()->count(2)->create([
        'hydrometer_id' => $hydrometer->id,
        'resolved' => false,
    ]);

    // Act
    $response = $this->actingAs($user)->getJson('/api/dashboard/summary');

    // Assert
    $response->assertOk()
        ->assertJson([
            'total_hydrometers' => 8,
            'online' => 5,
            'offline' => 2,
            'alert' => 1,
            'total_readings_today' => 3,
            'pending_alerts' => 2,
        ]);
});

it('retorna dados do grafico de consumo dentro do periodo recente', function () {
    $user = User::factory()->create();
    $hydrometer = Hydrometer::factory()->create();

    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 5.5,
        'reading_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($user)->getJson('/api/dashboard/consumption?days=30');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonStructure([['date', 'total_m3']]);
});

it('ancora o grafico de consumo na leitura mais recente como fallback para dados historicos', function () {
    $user = User::factory()->create();
    $hydrometer = Hydrometer::factory()->create();

    // Leituras de 60 dias atrás (fora dos últimos 30 dias a contar de hoje)
    $oldDate = now()->subDays(60);
    Reading::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'value_m3' => 4.2,
        'reading_at' => $oldDate,
    ]);

    $response = $this->actingAs($user)->getJson('/api/dashboard/consumption?days=30');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.date', $oldDate->toDateString());
});
