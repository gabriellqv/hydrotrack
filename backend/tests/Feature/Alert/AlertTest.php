<?php

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para os endpoints de alertas.
 *
 * Validam listagem paginada, resolução de alertas e
 * proteção de rotas contra acesso não autenticado.
 */
it('lista alertas paginados para usuário autenticado', function () {
    $user = User::factory()->create();
    $hydrometer = Hydrometer::factory()->create();

    Alert::factory()->count(3)->create([
        'hydrometer_id' => $hydrometer->id,
    ]);

    $response = $this->actingAs($user)->getJson('/api/alerts');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'type', 'message', 'resolved']],
            'meta',
        ])
        ->assertJsonCount(3, 'data');
});

it('marca um alerta como resolvido', function () {
    $user = User::factory()->create();
    $alert = Alert::factory()->create(['resolved' => false]);

    $response = $this->actingAs($user)
        ->patchJson("/api/alerts/{$alert->id}/resolve");

    $response->assertOk()
        ->assertJsonPath('resolved', true);

    expect($alert->fresh()->resolved)->toBeTrue();
    expect($alert->fresh()->resolved_at)->not->toBeNull();
});

it('bloqueia listagem de alertas sem autenticação', function () {
    $response = $this->getJson('/api/alerts');

    $response->assertStatus(401);
});

it('retorna métricas agregadas de alertas de todo o histórico', function () {
    $user = User::factory()->create();

    Alert::factory()->count(30)->create(['resolved' => false]);
    Alert::factory()->count(10)->create(['resolved' => true, 'resolved_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/alerts/stats');

    $response->assertOk()
        ->assertJson([
            'total' => 40,
            'resolved' => 10,
            'pending' => 30,
            'resolution_rate' => 25,
        ]);
});

it('considera todo o histórico e não apenas a primeira página de alertas', function () {
    $user = User::factory()->create();

    // Mais que o per_page (20) para garantir que stats não é derivado da listagem.
    Alert::factory()->count(25)->create(['resolved' => false]);

    $this->actingAs($user)->getJson('/api/alerts/stats')
        ->assertOk()
        ->assertJsonPath('total', 25)
        ->assertJsonPath('pending', 25);
});

it('retorna taxa de resolução de 100% quando não há alertas', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/alerts/stats')
        ->assertOk()
        ->assertJson([
            'total' => 0,
            'resolved' => 0,
            'pending' => 0,
            'resolution_rate' => 100,
        ]);
});

it('bloqueia as métricas de alertas sem autenticação', function () {
    $this->getJson('/api/alerts/stats')->assertStatus(401);
});
