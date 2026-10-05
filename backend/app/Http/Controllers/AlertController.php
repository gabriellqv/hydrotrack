<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller de alertas do sistema de monitoramento.
 *
 * Permite listar alertas pendentes e marcar alertas como resolvidos.
 */
class AlertController extends Controller
{
    /**
     * Lista todos os alertas com paginação, filtros e dados do hidrômetro.
     *
     * @param  Request  $request  Query params: type, resolved
     * @return AnonymousResourceCollection Coleção paginada de AlertResource
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alert::with('hydrometer')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('resolved')) {
            $query->where('resolved', $request->query('resolved') === 'true');
        }

        return AlertResource::collection($query->paginate(20));
    }

    /**
     * Retorna as métricas agregadas de alertas.
     *
     * Os KPIs da Central de Alertas precisam refletir todo o histórico,
     * e não apenas a página atual da listagem paginada. Usa uma única
     * agregação condicional para evitar 3 queries de COUNT separadas.
     *
     * @return JsonResponse Totais de alertas e taxa de resolução (%)
     */
    public function stats(): JsonResponse
    {
        // Usa o query builder para evitar o cast booleano do model, que
        // converteria o alias agregado `resolved` (contagem) em true/false.
        $stats = Alert::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END) as resolved_count')
            ->first();

        $total = (int) $stats->total;
        $resolved = (int) $stats->resolved_count;

        return response()->json([
            'total' => $total,
            'resolved' => $resolved,
            'pending' => $total - $resolved,
            'resolution_rate' => $total > 0 ? (int) round(($resolved / $total) * 100) : 100,
        ]);
    }

    /**
     * Marca um alerta como resolvido pelo operador.
     *
     * @param  Alert  $alert  Resolvido via Route Model Binding
     * @return JsonResponse Alerta atualizado
     */
    public function resolve(Alert $alert): JsonResponse
    {
        $alert->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        DashboardService::invalidateCache();

        return response()->json(new AlertResource($alert->fresh('hydrometer')));
    }
}
