<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        User::firstOrCreate(
            ['email' => 'admin@hydrotrack.com'],
            [
                'name' => 'Admin HydroTrack',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Cria um operador de exemplo
        User::firstOrCreate(
            ['email' => 'operador@hydrotrack.com'],
            [
                'name' => 'Operador Demo',
                'password' => Hash::make('operador123'),
                'role' => 'operator',
            ]
        );

        // Popula hidrômetros com leituras históricas
        $this->call(HydrometerSeeder::class);
    }
}
