<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory para geração de usuários fictícios.
 *
 * Utilizada em testes automatizados e seeders para criar
 * instâncias do modelo User com dados realistas via Faker.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Senha em cache reutilizada entre invocações da factory.
     */
    protected static ?string $password;

    /**
     * Define o estado padrão do modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica que o e-mail do usuário não deve estar verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
