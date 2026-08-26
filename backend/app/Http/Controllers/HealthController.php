<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controller de health check da API.
 *
 * Endpoint público utilizado por ferramentas de monitoramento
 * para verificar a disponibilidade do serviço e do banco de dados.
 */
class HealthController extends Controller
{
    /**
     * Verifica a saúde da aplicação.
     *
     * Executa uma consulta simples ao banco de dados e retorna 200
     * quando a conexão está ativa ou 503 caso o banco esteja indisponível.
     *
     * @return JsonResponse{status: string, database: bool}
     */
    public function check(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ok',
                'database' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'database' => false,
            ], 503);
        }
    }
}
