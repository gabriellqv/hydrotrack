<?php

use App\Models\Hydrometer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint PUT /api/hydrometers/{id}.
 *
 * Validam atualização parcial, proteção RBAC (admin vs operator),
 * rejeição de código duplicado e proteção contra acesso sem auth.
 */
it('permite que um admin atualize um hidrômetro', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $hydrometer = Hydrometer::factory()->create(['address' => 'Rua Antiga, 1']);

    $response = $this->actingAs($admin)->putJson("/api/hydrometers/{$hydrometer->id}", [
        'address' => 'Rua Nova, 200',
        'neighborhood' => 'Bonfim',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.address', 'Rua Nova, 200')
        ->assertJsonPath('data.neighborhood', 'Bonfim');

    $this->assertDatabaseHas('hydrometers', [
        'id' => $hydrometer->id,
        'address' => 'Rua Nova, 200',
    ]);
});

it('bloqueia operadores de atualizar hidrômetros', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->actingAs($operator)->putJson("/api/hydrometers/{$hydrometer->id}", [
        'address' => 'Rua Proibida',
    ]);

    $response->assertStatus(403);
});

it('rejeita atualização com código duplicado', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Hydrometer::factory()->create(['code' => 'HYD-EXISTE']);
    $hydrometer = Hydrometer::factory()->create(['code' => 'HYD-OUTRO']);

    $response = $this->actingAs($admin)->putJson("/api/hydrometers/{$hydrometer->id}", [
        'code' => 'HYD-EXISTE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('bloqueia atualização sem autenticação', function () {
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->putJson("/api/hydrometers/{$hydrometer->id}", [
        'address' => 'Rua Sem Auth',
    ]);

    $response->assertStatus(401);
});
