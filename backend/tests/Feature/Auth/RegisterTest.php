<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['auth.registration_enabled' => true]);
});

/**
 * Testes de integração para o endpoint POST /api/auth/register.
 *
 * Validam criação de usuário, duplicidade de email, regras
 * de senha mínima com confirmação obrigatória e controle
 * de registro público por flag de configuração.
 */
it('registra um novo usuário e retorna token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'João Operador',
        'email' => 'joao@hydrotrack.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', 'joao@hydrotrack.com');

    $this->assertDatabaseHas('users', ['email' => 'joao@hydrotrack.com']);
});

it('rejeita registro quando REGISTRATION_ENABLED esta false', function () {
    config(['auth.registration_enabled' => false]);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'João Operador',
        'email' => 'joao@hydrotrack.com',
        'password' => 'SenhaSegura123',
        'password_confirmation' => 'SenhaSegura123',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Registro publico esta desabilitado.');

    $this->assertDatabaseMissing('users', ['email' => 'joao@hydrotrack.com']);
});

it('rejeita registro com email já existente', function () {
    User::factory()->create(['email' => 'duplicado@hydrotrack.com']);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Outro Usuário',
        'email' => 'duplicado@hydrotrack.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejeita registro com senha menor que 8 caracteres', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Usuário Teste',
        'email' => 'teste@hydrotrack.com',
        'password' => 'curta',
        'password_confirmation' => 'curta',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});
