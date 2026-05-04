<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint POST /api/hydrometers.
 *
 * Validam o fluxo completo: autenticação → validação → criação → resposta,
 * incluindo cenários de erro (sem auth, dados inválidos, role insuficiente).
 */

it('permite que um admin crie um hidrômetro', function () {
    // Arrange: cria um usuário admin e autentica
    $admin = User::factory()->create(['role' => 'admin']);

    // Act: faz POST autenticado
    $response = $this->actingAs($admin)->postJson('/api/hydrometers', [
        'code' => 'HYD-001',
        'latitude' => -17.1085,
        'longitude' => -43.8143,
        'address' => 'Praça Wandick Dumont, 10',
        'neighborhood' => 'Centro',
        'type' => 'commercial',
    ]);

    // Assert: 201 Created com os dados corretos
    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'HYD-001')
        ->assertJsonPath('data.status', 'online');

    $this->assertDatabaseHas('hydrometers', ['code' => 'HYD-001']);
});

it('bloqueia operadores de criar hidrômetros', function () {
    $operator = User::factory()->create(['role' => 'operator']);

    $response = $this->actingAs($operator)->postJson('/api/hydrometers', [
        'code' => 'HYD-002',
        'latitude' => -17.1085,
        'longitude' => -43.8143,
        'address' => 'Rua Teste',
        'neighborhood' => 'Bonfim',
        'type' => 'residential',
    ]);

    $response->assertStatus(403);
});

it('rejeita criação sem autenticação', function () {
    $response = $this->postJson('/api/hydrometers', [
        'code' => 'HYD-003',
    ]);

    $response->assertStatus(401);
});

it('rejeita criação com código duplicado', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Cria o primeiro
    \App\Models\Hydrometer::factory()->create(['code' => 'HYD-DUP']);

    // Tenta criar com o mesmo código
    $response = $this->actingAs($admin)->postJson('/api/hydrometers', [
        'code' => 'HYD-DUP',
        'latitude' => -17.1,
        'longitude' => -43.8,
        'address' => 'Rua Duplicada',
        'neighborhood' => 'Centro',
        'type' => 'residential',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('rejeita coordenadas GPS fora do range válido', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson('/api/hydrometers', [
        'code' => 'HYD-GPS',
        'latitude' => 999,      // Inválido: máximo é 90
        'longitude' => -43.8,
        'address' => 'Rua GPS',
        'neighborhood' => 'Centro',
        'type' => 'residential',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('latitude');
});
