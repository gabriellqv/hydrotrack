<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Testes do middleware EnsureIsAdmin.
 *
 * Validam a protecao de rotas exclusivas para administradores.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->operator = User::factory()->create(['role' => 'operator']);
});

it('permite acesso para usuario admin', function () {
    Route::middleware(EnsureIsAdmin::class)
        ->get('/admin-test', fn () => response()->json(['ok' => true]));

    $this->actingAs($this->admin)
        ->getJson('/admin-test')
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('nega acesso para usuario nao admin', function () {
    Route::middleware(EnsureIsAdmin::class)
        ->get('/admin-test', fn () => response()->json(['ok' => true]));

    $this->actingAs($this->operator)
        ->getJson('/admin-test')
        ->assertForbidden()
        ->assertJson([
            'message' => 'Acesso negado. Apenas administradores podem realizar esta acao.',
        ]);
});

it('nega acesso para usuario sem autenticacao', function () {
    Route::middleware(EnsureIsAdmin::class)
        ->get('/admin-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/admin-test')
        ->assertForbidden();
});

it('nega acesso para usuario com role operator', function () {
    Route::middleware(EnsureIsAdmin::class)
        ->get('/admin-test', fn () => response()->json(['ok' => true]));

    $this->actingAs($this->operator)
        ->getJson('/admin-test')
        ->assertForbidden();
});
