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
