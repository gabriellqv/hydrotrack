<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint POST /api/auth/login.
 *
 * Validam o fluxo completo de autenticação: credenciais válidas,
 * credenciais inválidas, campos obrigatórios e formato da resposta.
 */
it('autentica com credenciais válidas e retorna token', function () {
    $user = User::factory()->create([
        'password' => bcrypt('senha-segura-123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'senha-segura-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', $user->email);
});

it('rejeita credenciais inválidas com 422', function () {
    $user = User::factory()->create([
        'password' => bcrypt('senha-correta'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'senha-errada',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejeita login com campos obrigatórios ausentes', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('retorna estrutura de resposta consistente com user e token', function () {
    $user = User::factory()->create([
        'password' => bcrypt('minha-senha-123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'minha-senha-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});
