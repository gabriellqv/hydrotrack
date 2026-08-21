<?php

use App\Models\Hydrometer;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Testes de integracao dos endpoints de detalhe e exportacao de hidrometros.
 *
 * Cobrem a exibicao de detalhes com leituras e o download CSV de leituras.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->hydrometer = Hydrometer::factory()->create();
});

it('retorna detalhes de um hidrometro com dados de consumo do periodo padrao', function () {
    $this->actingAs($this->user)
        ->getJson("/api/hydrometers/{$this->hydrometer->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->hydrometer->id);
});

it('permite filtrar dados de consumo do detalhe por periodo em dias', function () {
    $this->actingAs($this->user)
        ->getJson("/api/hydrometers/{$this->hydrometer->id}?days=30")
        ->assertOk()
        ->assertJsonPath('data.id', $this->hydrometer->id);
});

it('retorna 404 para hidrometro inexistente', function () {
    $this->actingAs($this->user)
        ->getJson('/api/hydrometers/99999')
        ->assertNotFound();
});

it('exporta leituras em formato csv', function () {
    Reading::factory()->count(2)->create([
        'hydrometer_id' => $this->hydrometer->id,
        'value_m3' => 12.345,
        'reading_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($this->user)
        ->get("/api/hydrometers/{$this->hydrometer->id}/readings/export");

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename='.$this->hydrometer->code.'_leituras.csv');

    $content = method_exists($response, 'streamedContent') ? $response->streamedContent() : $response->getContent();
    expect((string) $content)->toContain('Data');
    expect((string) $content)->toContain('Consumo');
    expect((string) $content)->toContain('12,345');
});

it('retorna csv vazio quando hidrometro nao possui leituras', function () {
    $response = $this->actingAs($this->user)
        ->get("/api/hydrometers/{$this->hydrometer->id}/readings/export");

    $response->assertOk();

    $content = method_exists($response, 'streamedContent') ? $response->streamedContent() : $response->getContent();
    expect((string) $content)->toContain('Data');
    expect((string) $content)->not->toContain('12,345');
});

it('bloqueia exportacao sem autenticacao', function () {
    $this->getJson("/api/hydrometers/{$this->hydrometer->id}/readings/export")
        ->assertStatus(401);
});
