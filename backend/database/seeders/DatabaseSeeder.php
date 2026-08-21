<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder principal do banco de dados.
 *
 * Orquestra a execução de todos os seeders da aplicação.
 * Cria o usuário administrador padrão e delega a criação de
 * hidrômetros e leituras ao HydrometerSeeder.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Popula o banco de dados com registros iniciais.
     */
    public function run(): void
    {
        // Cria o usuário administrador padrão
        $adminPassword = env('ADMIN_SEED_PASSWORD');
        if (! $adminPassword) {
            $adminPassword = Str::random(16);
            $this->command->warn("ADMIN_SEED_PASSWORD nao definida. Admin criado com senha temporaria: {$adminPassword}");
        }

        User::firstOrCreate(
            ['email' => 'admin@hydrotrack.com'],
            [
                'name' => 'Admin HydroTrack',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
            ]
        );

        // Cria um operador de exemplo
        $operatorPassword = env('OPERATOR_SEED_PASSWORD');
        if (! $operatorPassword) {
            $operatorPassword = Str::random(16);
            $this->command->warn("OPERATOR_SEED_PASSWORD nao definida. Operador criado com senha temporaria: {$operatorPassword}");
        }

        User::firstOrCreate(
            ['email' => 'operador@hydrotrack.com'],
            [
                'name' => 'Operador Demo',
                'password' => Hash::make($operatorPassword),
                'role' => 'operator',
            ]
        );

        // Popula hidrômetros com leituras históricas
        $this->call(HydrometerSeeder::class);
    }
}
