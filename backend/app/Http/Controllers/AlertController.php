<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller de alertas do sistema de monitoramento.
 *
 * Delega toda a logica de negocio ao AlertService.
 */
class AlertController extends Controller
{
    /**
     * @param  AlertService  $service  Injetado automaticamente pelo container do Laravel
     */
    public function __construct(
        private readonly AlertService $service
    ) {}

    /**
     * Lista todos os alertas com paginacao, filtros e dados do hidrometro.
     *
     * @param  Request  $request  Query params: type, resolved
     * @return AnonymousResourceCollection Colecao paginada de AlertResource
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AlertResource::collection($this->service->list($request));
    }

    /**
     * Marca um alerta como resolvido pelo operador.
     *
     * @param  Alert  $alert  Resolvido via Route Model Binding
     * @return JsonResponse Alerta atualizado
     */
    public function resolve(Alert $alert): JsonResponse
    {
        try {
            $updated = $this->service->resolve($alert);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json(new AlertResource($updated));
    }
}
