<?php

use App\Models\Hydrometer;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integração para o endpoint DELETE /api/hydrometers/{id}.
 *
 * Validam exclusão com cascade, proteção RBAC e rejeição sem auth.
 */
it('permite que um admin exclua um hidrômetro', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->actingAs($admin)->deleteJson("/api/hydrometers/{$hydrometer->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('hydrometers', ['id' => $hydrometer->id]);
});

it('exclui leituras em cascata ao remover hidrômetro', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $hydrometer = Hydrometer::factory()->create();

    Reading::factory()->count(5)->create([
        'hydrometer_id' => $hydrometer->id,
    ]);

    $this->assertDatabaseCount('readings', 5);

    $this->actingAs($admin)->deleteJson("/api/hydrometers/{$hydrometer->id}");

    $this->assertDatabaseCount('readings', 0);
});

it('bloqueia operadores de excluir hidrômetros', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->actingAs($operator)->deleteJson("/api/hydrometers/{$hydrometer->id}");

    $response->assertStatus(403);
});

it('bloqueia exclusão sem autenticação', function () {
    $hydrometer = Hydrometer::factory()->create();

    $response = $this->deleteJson("/api/hydrometers/{$hydrometer->id}");

    $response->assertStatus(401);
});
