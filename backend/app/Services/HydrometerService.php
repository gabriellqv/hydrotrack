<?php

namespace App\Services;

use App\Models\Hydrometer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Serviço responsável por toda a lógica de negócio relacionada a hidrômetros.
 *
 * Centraliza operações de CRUD, filtragem e regras de domínio,
 * mantendo o Controller livre de qualquer lógica além de I/O.
 */
class HydrometerService
{
    /**
     * Lista hidrômetros com paginação e filtros opcionais.
     *
     * @param array{
     *   neighborhood?: string,
     *   status?: string,
     *   type?: string,
     *   search?: string,
     *   per_page?: int
     * } $filters Filtros opcionais para a consulta
     * @return LengthAwarePaginator Resultado paginado de hidrômetros
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Hydrometer::query();

        if (isset($filters['neighborhood'])) {
            $query->byNeighborhood($filters['neighborhood']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('code')->paginate($perPage);
    }

    /**
     * Cria um novo hidrômetro no sistema.
     *
     * @param  array  $data  Dados validados pelo StoreHydrometerRequest
     * @return Hydrometer O hidrômetro recém-criado
     */
    public function create(array $data): Hydrometer
    {
        return Hydrometer::create($data)->fresh();
    }

    /**
     * Atualiza os dados de um hidrômetro existente.
     *
     * @param  Hydrometer  $hydrometer  Instância do hidrômetro a ser atualizado
     * @param  array  $data  Dados validados pelo UpdateHydrometerRequest
     * @return Hydrometer O hidrômetro atualizado
     */
    public function update(Hydrometer $hydrometer, array $data): Hydrometer
    {
        $hydrometer->update($data);

        return $hydrometer->fresh();
    }

    /**
     * Remove um hidrômetro do sistema.
     *
     * As leituras e alertas associados serão removidos em cascata
     * conforme definido na migration (cascadeOnDelete).
     *
     * @param  Hydrometer  $hydrometer  Instância a ser removida
     * @return bool True se a exclusão foi bem-sucedida
     */
    public function delete(Hydrometer $hydrometer): bool
    {
        return $hydrometer->delete();
    }

    /**
     * Retorna os detalhes de um hidrômetro com alertas recentes e dados de consumo.
     *
     * @param  Hydrometer  $hydrometer  Instância do hidrômetro
     * @param  int  $days  Período em dias para o gráfico de consumo
     * @return Hydrometer
     */
    public function getDetails(Hydrometer $hydrometer, int $days): Hydrometer
    {
        $days = min(max($days, 1), 365);

        $hydrometer->load([
            'alerts' => function ($q) {
                $q->latest()->limit(10);
            },
        ]);

        $hydrometer->chart_data = $hydrometer->readings()
            ->selectRaw('DATE(reading_at) as date, SUM(value_m3) as total_m3')
            ->where('reading_at', '>=', now()->subDays($days))
            ->groupByRaw('DATE(reading_at)')
            ->orderByRaw('DATE(reading_at)')
            ->get();

        return $hydrometer;
    }
}
