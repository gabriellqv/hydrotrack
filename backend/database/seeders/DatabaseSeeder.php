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
        User::factory()->create([
            'name' => 'Admin HydroTrack',
            'email' => 'admin@hydrotrack.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Cria um operador de exemplo
        User::factory()->create([
            'name' => 'Operador Demo',
            'email' => 'operador@hydrotrack.com',
            'password' => Hash::make('operador123'),
            'role' => 'operator',
        ]);

        // Popula hidrômetros com leituras históricas
        $this->call(HydrometerSeeder::class);
    }
}
