<?php

namespace Database\Factories;

use App\Models\Hydrometer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para gerar hidrômetros com dados realistas para testes.
 *
 * Gera coordenadas GPS dentro do raio urbano de Bocaiúva-MG
 * e distribui status aleatoriamente entre online/offline/alert.
 *
 * @extends Factory<Hydrometer>
 */
class HydrometerFactory extends Factory
{
    protected $model = Hydrometer::class;

    /**
     * Define o estado padrão do model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'HYD-' . $this->faker->unique()->randomNumber(3, true),
            'latitude' => $this->faker->latitude(-17.13, -17.08),
            'longitude' => $this->faker->longitude(-43.84, -43.79),
            'address' => $this->faker->streetAddress(),
            'neighborhood' => $this->faker->randomElement([
                'Centro', 'Pernambuco', 'Bonfim', 'Alterosa', 'São José',
            ]),
            'status' => $this->faker->randomElement(['online', 'offline', 'alert']),
            'type' => $this->faker->randomElement(['residential', 'commercial', 'industrial']),
            'last_reading_at' => $this->faker->dateTimeBetween('-48 hours', 'now'),
        ];
    }

    /**
     * Estado: hidrômetro online (funcionando normalmente).
     */
    public function online(): static
    {
        return $this->state(fn () => ['status' => 'online']);
    }

    /**
     * Estado: hidrômetro offline (sem comunicação).
     */
    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => 'offline',
            'last_reading_at' => now()->subDays(3),
        ]);
    }
}
