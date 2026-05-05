<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller de alertas do sistema de monitoramento.
 *
 * Permite listar alertas pendentes e marcar alertas como resolvidos.
 */
class AlertController extends Controller
{
    /**
     * Lista todos os alertas com paginação, incluindo dados do hidrômetro.
     *
     * @return AnonymousResourceCollection Coleção paginada de AlertResource
     */
    public function index(): AnonymousResourceCollection
    {
        $alerts = Alert::with('hydrometer')
            ->latest()
            ->paginate(20);

        return AlertResource::collection($alerts);
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
