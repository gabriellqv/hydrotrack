<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestReadingRequest;
use App\Http\Resources\ReadingResource;
use App\Models\Hydrometer;
use App\Models\Reading;
use Illuminate\Http\JsonResponse;

/**
 * Controller de ingestão de dados M2M (Machine-to-Machine).
 *
 * Recebe leituras de consumo enviadas pelo simulador IoT ou por
 * dispositivos reais em campo. A autenticação é feita via API key
 * no header, separada do fluxo Sanctum.
 */
class IngestController extends Controller
{
    /**
     * Registra uma nova leitura de consumo hídrico.
     *
     * Localiza o hidrômetro pelo código, cria a leitura e atualiza
     * o timestamp da última leitura no hidrômetro.
     *
     * @param  IngestReadingRequest  $request  Dados validados: hydrometer_code, value_m3, reading_at
     * @return JsonResponse 201 Created com a leitura registrada
     */
    public function store(IngestReadingRequest $request): JsonResponse
    {
        $hydrometer = Hydrometer::where('code', $request->hydrometer_code)->firstOrFail();

        $reading = Reading::create([
            'hydrometer_id' => $hydrometer->id,
            'value_m3' => $request->value_m3,
            'reading_at' => $request->reading_at,
        ]);

        $hydrometer->update([
            'last_reading_at' => $request->reading_at,
            'status' => 'online',
        ]);

        return (new ReadingResource($reading))
            ->response()
            ->setStatusCode(201);
    }
}
