<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller do dashboard analítico.
 *
 * Fornece endpoints para os cards de resumo, gráficos de consumo
 * e dados de posicionamento para o mapa interativo.
 */
class DashboardController extends Controller
{
    /**
     * @param  DashboardService  $service  Injetado automaticamente pelo container do Laravel
     */
    public function __construct(
        private readonly DashboardService $service
    ) {}

    /**
     * Retorna o resumo geral do sistema (cards do dashboard).
     *
     * @return JsonResponse Totais de hidrômetros, leituras e alertas
     */
    public function summary(): JsonResponse
    {
        return response()->json($this->service->getSummary());
    }

    /**
     * Retorna dados de consumo para alimentar gráficos de linha.
     *
     * @param  Request  $request  Query param: days (default: 30)
     * @return JsonResponse Consumo agregado por dia
     */
    public function consumption(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);

        return response()->json($this->service->getConsumptionChart($days));
    }

    /**
     * Retorna hidrômetros com coordenadas para renderizar no mapa.
     *
     * @return JsonResponse Coleção de hidrômetros com lat/lng e status
     */
    public function map(): JsonResponse
    {
        return response()->json($this->service->getMapData());
    }
}
