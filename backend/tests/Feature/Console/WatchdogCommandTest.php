<?php

use App\Console\Commands\WatchdogCommand;
use App\Models\Hydrometer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Testes de integracao do WatchdogCommand.
 *
 * Cobrem a deteccao automatica de hidrometros sem comunicacao,
 * marcacao em lote como offline e geracao de alertas.
 */
it('marca hidrometros silenciosos como offline e gera alertas', function () {
    $silent = Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => now()->subHours(25),
    ]);

    $recent = Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => now()->subHours(23),
    ]);

    $this->artisan(WatchdogCommand::class)
        ->assertSuccessful();

    $silent->refresh();
    $recent->refresh();

    expect($silent->status)->toBe('offline');
    expect($recent->status)->toBe('online');

    $this->assertDatabaseHas('alerts', [
        'hydrometer_id' => $silent->id,
        'type' => 'offline',
        'resolved' => false,
    ]);

    $this->assertDatabaseMissing('alerts', [
        'hydrometer_id' => $recent->id,
    ]);
});

it('considera hidrometros sem leitura como silenciosos', function () {
    $neverRead = Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => null,
    ]);

    $this->artisan(WatchdogCommand::class)
        ->assertSuccessful();

    $neverRead->refresh();
    expect($neverRead->status)->toBe('offline');
});

it('ignora hidrometros ja offline', function () {
    $alreadyOffline = Hydrometer::factory()->create([
        'status' => 'offline',
        'last_reading_at' => now()->subHours(48),
    ]);

    $this->artisan(WatchdogCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseCount('alerts', 0);

    $alreadyOffline->refresh();
    expect($alreadyOffline->status)->toBe('offline');
});

it('respeita threshold customizado via opcao', function () {
    $silent = Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => now()->subHours(10)->subMinute(),
    ]);

    $this->artisan(WatchdogCommand::class, ['--threshold' => 11])
        ->assertSuccessful();

    $silent->refresh();
    expect($silent->status)->toBe('online');

    $this->artisan(WatchdogCommand::class, ['--threshold' => 9])
        ->assertSuccessful();

    $silent->refresh();
    expect($silent->status)->toBe('offline');
});

it('invalida cache do dashboard apos detectar offline', function () {
    Cache::spy();

    Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => now()->subHours(25),
    ]);

    $this->artisan(WatchdogCommand::class)
        ->assertSuccessful();

    Cache::shouldHaveReceived('forget')->with('dashboard:summary');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:7');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:30');
    Cache::shouldHaveReceived('forget')->with('dashboard:consumption:90');
    Cache::shouldHaveReceived('forget')->with('dashboard:map');
});

it('nao gera alertas quando todos hidrometros comunicam normalmente', function () {
    Hydrometer::factory()->create([
        'status' => 'online',
        'last_reading_at' => now()->subMinute(),
    ]);

    $this->artisan(WatchdogCommand::class)
        ->assertSuccessful();

    $this->assertDatabaseCount('alerts', 0);
});
