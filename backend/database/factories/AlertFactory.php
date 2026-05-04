<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Hydrometer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para a model Alert.
 *
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'hydrometer_id' => Hydrometer::factory(),
            'type' => $this->faker->randomElement(['high_consumption', 'zero_reading', 'offline']),
            'message' => $this->faker->sentence(),
            'resolved' => false,
            'resolved_at' => null,
        ];
    }
}
