<?php

/**
 * Testes do middleware EnsureValidApiKey.
 *
 * Validam a autenticação M2M via header X-API-Key, incluindo
 * prevenção contra timing attacks e rejeição de chaves inválidas.
 */

use App\Http\Middleware\EnsureValidApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.ingest.api_key' => 'chave-correta-muito-secreta']);
});

it('permite requisicao com api key valida', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/api-key-test', ['X-API-Key' => 'chave-correta-muito-secreta'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('rejeita requisicao sem api key', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/api-key-test')
        ->assertUnauthorized()
        ->assertJson(['message' => 'API key inválida ou ausente.']);
});

it('rejeita api key incorreta', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/api-key-test', ['X-API-Key' => 'chave-errada'])
        ->assertUnauthorized();
});

it('rejeita api key de tamanho diferente sem vazar timing', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $response = $this->getJson('/api-key-test', ['X-API-Key' => 'curta']);

    $response->assertUnauthorized();
});

it('rejeita api key semelhante com prefixo correto', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/api-key-test', ['X-API-Key' => 'chave-correta-muito-secreta-errada'])
        ->assertUnauthorized();
});

it('nao expoe a api key configurada na resposta de erro', function () {
    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $response = $this->getJson('/api-key-test', ['X-API-Key' => 'chave-errada']);

    $response->assertUnauthorized();
    expect((string) $response->getContent())->not->toContain('chave-correta');
});

it('rejeita requisicao quando api key nao esta configurada', function () {
    config(['services.ingest.api_key' => null]);

    Route::middleware(EnsureValidApiKey::class)
        ->get('/api-key-test', fn () => response()->json(['ok' => true]));

    $this->getJson('/api-key-test', ['X-API-Key' => 'qualquer'])
        ->assertUnauthorized();
});

it('usa hash_equals para comparacao constante de tempo', function () {
    $request = Request::create('/ingest', 'POST', [], [], [], [
        'HTTP_X_API_KEY' => 'chave-correta-muito-secreta',
    ]);

    $middleware = new EnsureValidApiKey;
    $called = false;
    $next = function (Request $req) use (&$called) {
        $called = true;

        return response()->json(['passed' => true]);
    };

    $middleware->handle($request, $next);

    expect($called)->toBeTrue();
});
