<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Serviço responsável por consolidar dados analíticos para o dashboard.
 *
 * Fornece métricas agregadas de consumo, contagens de dispositivos por status
 * e dados formatados para alimentar gráficos e o mapa interativo.
 */
class DashboardService
{
    /**
     * Retorna o resumo geral do sistema para os cards do dashboard.
     *
     * @return array{
     *   total_hydrometers: int,
     *   online: int,
     *   offline: int,
     *   alert: int,
     *   total_readings_today: int,
     *   pending_alerts: int
     * }
     */
    public function getSummary(): array
    {
        return [
            'total_hydrometers' => Hydrometer::count(),
            'online' => Hydrometer::where('status', 'online')->count(),
            'offline' => Hydrometer::where('status', 'offline')->count(),
            'alert' => Hydrometer::where('status', 'alert')->count(),
            'total_readings_today' => Reading::whereDate('reading_at', Carbon::today())->count(),
            'pending_alerts' => Alert::where('resolved', false)->count(),
        ];
    }

    /**
     * Retorna dados de consumo agregados por dia para alimentar gráficos de linha.
     *
     * @param  int  $days  Número de dias retroativos (default: 30)
     * @return array<int, array{date: string, total_m3: float}>
     */
    public function getConsumptionChart(int $days = 30): array
    {
        return Reading::query()
            ->selectRaw('DATE(reading_at) as date, SUM(value_m3) as total_m3')
            ->where('reading_at', '>=', Carbon::now()->subDays($days))
            ->groupByRaw('DATE(reading_at)')
            ->orderByRaw('DATE(reading_at)')
            ->get()
            ->toArray();
    }

    /**
     * Retorna todos os hidrômetros com coordenadas e status para renderizar no mapa.
     *
     * @return Collection Coleção de hidrômetros com campos selecionados
     */
    public function getMapData()
    {
        return Hydrometer::select('id', 'code', 'latitude', 'longitude', 'address', 'neighborhood', 'type', 'status', 'last_reading_at')
            ->get();
    }
}
