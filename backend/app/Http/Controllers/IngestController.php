<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestReadingRequest;
use App\Http\Resources\ReadingResource;
use App\Services\ReadingService;
use Illuminate\Http\JsonResponse;

/**
 * Controller de ingestão de dados M2M (Machine-to-Machine).
 *
 * Recebe leituras de consumo enviadas pelo simulador IoT ou por
 * dispositivos reais em campo. A autenticação é feita via API key
 * no header, separada do fluxo Sanctum.
 *
 * Delega toda a lógica de negócio ao ReadingService, mantendo
 * o controller fino (apenas I/O).
 */
class IngestController extends Controller
{
    public function __construct(
        private readonly ReadingService $readingService
    ) {}

    /**
     * Registra uma nova leitura de consumo hídrico.
     *
     * Delega ao ReadingService que: persiste a leitura, atualiza o
     * status do hidrômetro, verifica limiares de alerta e invalida cache.
     *
     * @param  IngestReadingRequest  $request  Dados validados: hydrometer_code, value_m3, reading_at
     * @return JsonResponse 201 Created com a leitura registrada
     */
    public function store(IngestReadingRequest $request): JsonResponse
    {
        $reading = $this->readingService->ingest([
            'hydrometer_code' => $request->hydrometer_code,
            'value_m3' => $request->value_m3,
            'reading_at' => $request->reading_at,
        ]);

        return (new ReadingResource($reading))
            ->response()
            ->setStatusCode(201);
    }
}
