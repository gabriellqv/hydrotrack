<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço responsável por consolidar dados analíticos para o dashboard.
 *
 * Fornece métricas agregadas de consumo, contagens de dispositivos por status
 * e dados formatados para alimentar gráficos e o mapa interativo.
 *
 * Utiliza cache-aside pattern com invalidação event-driven via IngestController.
 * Em produção, o driver pode ser migrado de 'database' para Redis sem alteração de código.
 */
class DashboardService
{
    /** TTL do cache de summary e consumption em segundos */
    private const CACHE_TTL_SHORT = 60;

    /** TTL do cache de mapa em segundos */
    private const CACHE_TTL_MAP = 300;

    /**
     * Retorna o resumo geral do sistema para os cards do dashboard.
     *
     * Cacheia por 60 segundos para evitar 6 queries a cada request.
     * Invalidado automaticamente quando uma nova leitura é ingerida.
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
        return Cache::remember('dashboard:summary', self::CACHE_TTL_SHORT, function () {
            return [
                'total_hydrometers' => Hydrometer::count(),
                'online' => Hydrometer::where('status', 'online')->count(),
                'offline' => Hydrometer::where('status', 'offline')->count(),
                'alert' => Hydrometer::where('status', 'alert')->count(),
                'total_readings_today' => Reading::whereDate('reading_at', Carbon::today())->count(),
                'pending_alerts' => Alert::where('resolved', false)->count(),
            ];
        });
    }

    /**
     * Retorna dados de consumo agregados por dia para alimentar gráficos de linha.
     *
     * Cacheia por 60 segundos com chave parametrizada pelo número de dias,
     * permitindo cache independente para diferentes períodos.
     *
     * @param  int  $days  Número de dias retroativos (default: 30)
     * @return array<int, array{date: string, total_m3: float}>
     */
    public function getConsumptionChart(int $days = 30): array
    {
        return Cache::remember("dashboard:consumption:{$days}", self::CACHE_TTL_SHORT, function () use ($days) {
            return Reading::query()
                ->selectRaw('DATE(reading_at) as date, SUM(value_m3) as total_m3')
                ->where('reading_at', '>=', Carbon::now()->subDays($days))
                ->groupByRaw('DATE(reading_at)')
                ->orderByRaw('DATE(reading_at)')
                ->get()
                ->toArray();
        });
    }

    /**
     * Retorna todos os hidrômetros com coordenadas e status para renderizar no mapa.
     *
     * Cacheia por 5 minutos — dados de posicionamento mudam com pouca frequência.
     *
     * @return Collection Coleção de hidrômetros com campos selecionados
     */
    public function getMapData()
    {
        return Cache::remember('dashboard:map', self::CACHE_TTL_MAP, function () {
            return Hydrometer::select('id', 'code', 'latitude', 'longitude', 'address', 'neighborhood', 'type', 'status', 'last_reading_at')
                ->get()
                ->toArray();
        });
    }

    /**
     * Invalida todos os caches do dashboard.
     *
     * Chamado pelo IngestController após registrar uma nova leitura,
     * garantindo que os dados exibidos estejam sempre atualizados.
     */
    public static function invalidateCache(): void
    {
        Cache::forget('dashboard:summary');
        Cache::forget('dashboard:consumption:7');
        Cache::forget('dashboard:consumption:30');
        Cache::forget('dashboard:consumption:90');
        Cache::forget('dashboard:map');
    }
}
