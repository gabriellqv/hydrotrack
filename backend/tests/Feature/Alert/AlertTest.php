<?php

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Testes de integracao para os endpoints de alertas.
 *
 * Validam listagem paginada, resolucao de alertas e
 * protecao de rotas contra acesso nao autenticado.
 */
it('lista alertas paginados para usuario autenticado', function () {
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

it('permite que um admin resolva um alerta', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $alert = Alert::factory()->create(['resolved' => false]);

    $response = $this->actingAs($admin)
        ->patchJson("/api/alerts/{$alert->id}/resolve");

    $response->assertOk()
        ->assertJsonPath('resolved', true);

    expect($alert->fresh()->resolved)->toBeTrue();
    expect($alert->fresh()->resolved_at)->not->toBeNull();
});

it('bloqueia operadores de resolverem alertas', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $alert = Alert::factory()->create(['resolved' => false]);

    $response = $this->actingAs($operator)
        ->patchJson("/api/alerts/{$alert->id}/resolve");

    $response->assertStatus(403);

    expect($alert->fresh()->resolved)->toBeFalse();
    expect($alert->fresh()->resolved_at)->toBeNull();
});

it('rejeita resolucao de alerta ja resolvido com 409', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $alert = Alert::factory()->create(['resolved' => true, 'resolved_at' => now()]);

    $response = $this->actingAs($admin)
        ->patchJson("/api/alerts/{$alert->id}/resolve");

    $response->assertStatus(409)
        ->assertJsonPath('message', 'Alerta ja resolvido.');

    expect($alert->fresh()->resolved_at)->not->toBeNull();
});

it('bloqueia listagem de alertas sem autenticacao', function () {
    $response = $this->getJson('/api/alerts');

    $response->assertStatus(401);
});

it('filtra alertas por tipo e status resolvido', function () {
    $user = User::factory()->create();
    $hydrometer = Hydrometer::factory()->create();

    Alert::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'type' => 'offline',
        'resolved' => false,
    ]);

    Alert::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
        'resolved' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/alerts?type=offline&resolved=false');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'offline')
        ->assertJsonPath('data.0.resolved', false);
});

it('invalida cache do dashboard ao resolver um alerta', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $alert = Alert::factory()->create(['resolved' => false]);

    Cache::spy();

    $this->actingAs($admin)
        ->patchJson("/api/alerts/{$alert->id}/resolve")
        ->assertOk();

    Cache::shouldHaveReceived('forget')
        ->with('dashboard:summary');
    Cache::shouldHaveReceived('forget')
        ->with('dashboard:map');
});
