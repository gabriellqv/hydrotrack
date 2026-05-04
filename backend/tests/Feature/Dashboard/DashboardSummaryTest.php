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
