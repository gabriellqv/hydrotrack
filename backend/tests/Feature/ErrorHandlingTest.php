<?php

use App\Models\Hydrometer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes para o tratamento global de excecoes da API.
 *
 * Validam que erros comuns retornam respostas JSON padronizadas.
 */
it('retorna 404 padronizado para recurso nao encontrado', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/hydrometers/99999');

    $response->assertStatus(404)
        ->assertJson(['message' => 'Recurso nao encontrado.']);
});

it('retorna 401 padronizado para requisicao nao autenticada', function () {
    $response = $this->getJson('/api/hydrometers');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Nao autenticado.']);
});

it('retorna 403 padronizado para usuario sem permissao', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->actingAs($operator)->deleteJson("/api/hydrometers/{$hydrometer->id}");

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Acesso negado. Apenas administradores podem realizar esta acao.');
});

it('retorna 422 padronizado com erros de validacao', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson('/api/hydrometers', [
        'code' => '',
        'latitude' => 999,
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
});

it('adiciona headers de seguranca nas respostas', function () {
    $response = $this->getJson('/api/hydrometers');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-XSS-Protection', '1; mode=block')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
