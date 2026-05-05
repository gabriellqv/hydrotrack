<?php

use App\Models\Hydrometer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint GET /api/hydrometers.
 *
 * Validam listagem com paginação, filtros por bairro e status,
 * e proteção contra acesso não autenticado.
 */
it('lista hidrômetros paginados para usuário autenticado', function () {
    $user = User::factory()->create();
    Hydrometer::factory()->count(20)->create();

    $response = $this->actingAs($user)->getJson('/api/hydrometers');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'status', 'type', 'neighborhood']],
            'meta',
        ]);

    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('meta.total'))->toBe(20);
});

it('filtra hidrômetros por bairro', function () {
    $user = User::factory()->create();

    Hydrometer::factory()->count(3)->create(['neighborhood' => 'Centro']);
    Hydrometer::factory()->count(2)->create(['neighborhood' => 'Bonfim']);

    $response = $this->actingAs($user)
        ->getJson('/api/hydrometers?neighborhood=Centro');

    $response->assertOk();

    expect($response->json('meta.total'))->toBe(3);
});

it('filtra hidrômetros por status', function () {
    $user = User::factory()->create();

    Hydrometer::factory()->count(4)->create(['status' => 'online']);
    Hydrometer::factory()->count(1)->create(['status' => 'offline']);

    $response = $this->actingAs($user)
        ->getJson('/api/hydrometers?status=offline');

    $response->assertOk();

    expect($response->json('meta.total'))->toBe(1);
});

it('bloqueia listagem sem autenticação', function () {
    $response = $this->getJson('/api/hydrometers');

    $response->assertStatus(401);
});
