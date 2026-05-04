<?php

namespace Database\Factories;

use App\Models\Reading;
use App\Models\Hydrometer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para a model Reading.
 *
 * @extends Factory<Reading>
 */
class ReadingFactory extends Factory
{
    protected $model = Reading::class;

    public function definition(): array
    {
        return [
            'hydrometer_id' => Hydrometer::factory(),
            'value_m3' => $this->faker->randomFloat(3, 0.1, 10.0),
            'reading_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
