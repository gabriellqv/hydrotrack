<?php

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Services\AlertService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/**
 * Testes unitarios do AlertService.
 *
 * Validam a logica de negocio de listagem e resolucao de alertas
 * de forma isolada do Controller.
 */
beforeEach(function () {
    $this->service = new AlertService;
});

it('lista alertas paginados sem filtros', function () {
    $hydrometer = Hydrometer::factory()->create();
    Alert::factory()->count(3)->create(['hydrometer_id' => $hydrometer->id]);

    $request = new Request;
    $result = $this->service->list($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(3);
});

it('filtra alertas por tipo', function () {
    $hydrometer = Hydrometer::factory()->create();
    Alert::factory()->count(2)->create([
        'hydrometer_id' => $hydrometer->id,
        'type' => 'offline',
    ]);
    Alert::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'type' => 'high_consumption',
    ]);

    $request = new Request(['type' => 'offline']);
    $result = $this->service->list($request);

    expect($result->total())->toBe(2);
});

it('filtra alertas por status resolvido', function () {
    $hydrometer = Hydrometer::factory()->create();
    Alert::factory()->count(2)->create([
        'hydrometer_id' => $hydrometer->id,
        'resolved' => true,
    ]);
    Alert::factory()->create([
        'hydrometer_id' => $hydrometer->id,
        'resolved' => false,
    ]);

    $request = new Request(['resolved' => 'true']);
    $result = $this->service->list($request);

    expect($result->total())->toBe(2);
});

it('marca um alerta como resolvido', function () {
    $alert = Alert::factory()->create(['resolved' => false]);

    $updated = $this->service->resolve($alert);

    expect($updated->resolved)->toBeTrue();
    expect($updated->resolved_at)->not->toBeNull();
});

it('lanca excecao ao tentar resolver alerta ja resolvido', function () {
    $alert = Alert::factory()->create(['resolved' => true, 'resolved_at' => now()]);

    expect(fn () => $this->service->resolve($alert))
        ->toThrow(RuntimeException::class, 'Alerta ja resolvido.');
});
