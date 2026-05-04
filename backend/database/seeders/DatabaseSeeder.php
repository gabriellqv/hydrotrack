<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder principal do banco de dados.
 *
 * Orquestra a execução de todos os seeders da aplicação.
 * Utiliza WithoutModelEvents para evitar disparos de eventos
 * durante a população inicial dos dados.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Popula o banco de dados com registros iniciais.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
