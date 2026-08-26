<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Serviço responsável pela lógica de negócio de alertas.
 *
 * Centraliza listagem paginada, filtragem e resolução de alertas,
 * mantendo o controller fino e alinhado com o padrão Service do projeto.
 */
class AlertService
{
    /**
     * Lista alertas paginados com filtros opcionais.
     *
     * @return LengthAwarePaginator Coleção paginada de alertas com hidrômetro
     */
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Alert::with('hydrometer')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('resolved')) {
            $query->where('resolved', $request->query('resolved') === 'true');
        }

        return $query->paginate(20);
    }

    /**
     * Marca um alerta como resolvido.
     *
     * Atualiza os campos `resolved` e `resolved_at` e invalida o cache
     * do dashboard para refletir a redução de alertas pendentes.
     *
     * @param  Alert  $alert  Instância do alerta a ser resolvido
     * @return Alert Alerta atualizado
     *
     * @throws \RuntimeException Se o alerta já estiver resolvido
     */
    public function resolve(Alert $alert): Alert
    {
        if ($alert->resolved) {
            throw new \RuntimeException('Alerta já resolvido.');
        }

        $alert->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        DashboardService::invalidateCache();

        Log::info('Alerta resolvido', [
            'alert_id' => $alert->id,
            'hydrometer_id' => $alert->hydrometer_id,
        ]);

        return $alert->fresh('hydrometer');
    }
}
