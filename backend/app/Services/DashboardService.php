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

    /** Periodos disponiveis para o grafico de consumo */
    private const CONSUMPTION_PERIODS = [7, 30, 90];

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
            // Agregação condicional: 1 query em vez de 4 para contagens de status
            $stats = Hydrometer::selectRaw("
                COUNT(*) as total_hydrometers,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
                SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
                SUM(CASE WHEN status = 'alert' THEN 1 ELSE 0 END) as alert
            ")->first();

            return [
                'total_hydrometers' => (int) $stats->total_hydrometers,
                'online' => (int) $stats->online,
                'offline' => (int) $stats->offline,
                'alert' => (int) $stats->alert,
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
        $cacheKey = $this->getConsumptionCacheKey($days);

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($days) {
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
     * Retorna a chave de cache padronizada para o grafico de consumo.
     */
    public function getConsumptionCacheKey(int $days): string
    {
        return "dashboard:consumption:{$days}";
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

        foreach (self::CONSUMPTION_PERIODS as $days) {
            Cache::forget("dashboard:consumption:{$days}");
        }

        Cache::forget('dashboard:map');
    }

    /**
     * Retorna os periodos de consumo suportados pelo cache.
     *
     * @return array<int>
     */
    public static function getConsumptionPeriods(): array
    {
        return self::CONSUMPTION_PERIODS;
    }
}
