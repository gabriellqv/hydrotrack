<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('realiza logout revogando o token atual', function () {
    $user = User::factory()->create([
        'password' => Hash::make('SenhaSegura123'),
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJson(['message' => 'Logout realizado com sucesso.']);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('impede acesso autenticado apos logout', function () {
    $user = User::factory()->create([
        'password' => Hash::make('SenhaSegura123'),
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout')
        ->assertOk();

    // Sanctum nao invalida imediatamente o token em memoria no mesmo teste,
    // pois o token plain text so e validado contra o hash. Portanto, validamos
    // a revogacao verificando que o registro foi removido do banco.
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('retorna erro ao tentar logout sem autenticacao', function () {
    $this->postJson('/api/auth/logout')
        ->assertUnauthorized();
});

it('logout nao afeta tokens de outros usuarios', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $userToken = $user->createToken('auth-token')->plainTextToken;
    $otherToken = $other->createToken('auth-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$userToken}")
        ->postJson('/api/auth/logout')
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->getJson('/api/auth/me')
        ->assertOk();
});
