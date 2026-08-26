<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHydrometerRequest;
use App\Http\Requests\UpdateHydrometerRequest;
use App\Http\Resources\HydrometerResource;
use App\Http\Resources\ReadingResource;
use App\Models\Hydrometer;
use App\Services\HydrometerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller de hidrômetros.
 *
 * Responsável exclusivamente por receber requests HTTP, delegar ao
 * HydrometerService e formatar a response via HydrometerResource.
 * Zero lógica de negócio aqui; apenas I/O.
 */
class HydrometerController extends Controller
{
    /**
     * @param  HydrometerService  $service  Injetado automaticamente pelo container do Laravel
     */
    public function __construct(
        private readonly HydrometerService $service
    ) {}

    /**
     * Lista todos os hidrômetros com paginação e filtros opcionais.
     *
     * @param  Request  $request  Query params: neighborhood, status, type, per_page
     * @return AnonymousResourceCollection Coleção paginada de HydrometerResource
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hydrometers = $this->service->list($request->only([
            'neighborhood', 'status', 'type', 'per_page', 'search',
        ]));

        return HydrometerResource::collection($hydrometers);
    }

    /**
     * Exibe os detalhes de um hidrômetro específico.
     *
     * @param  Request  $request  Query params: days (7, 30, 90)
     * @param  Hydrometer  $hydrometer  Resolvido automaticamente via Route Model Binding
     */
    public function show(Request $request, Hydrometer $hydrometer): HydrometerResource
    {
        $days = (int) $request->query('days', 30);

        return new HydrometerResource($this->service->getDetails($hydrometer, $days));
    }

    /**
     * Cria um novo hidrômetro.
     *
     * @param  StoreHydrometerRequest  $request  Dados já validados pelo Form Request
     * @return JsonResponse 201 Created com o recurso criado
     */
    public function store(StoreHydrometerRequest $request): JsonResponse
    {
        $hydrometer = $this->service->create($request->validated());

        return (new HydrometerResource($hydrometer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualiza um hidrômetro existente.
     *
     * @param  UpdateHydrometerRequest  $request  Dados já validados
     * @param  Hydrometer  $hydrometer  Resolvido via Route Model Binding
     */
    public function update(UpdateHydrometerRequest $request, Hydrometer $hydrometer): HydrometerResource
    {
        $updated = $this->service->update($hydrometer, $request->validated());

        return new HydrometerResource($updated);
    }

    /**
     * Remove um hidrômetro do sistema.
     *
     * @param  Hydrometer  $hydrometer  Resolvido via Route Model Binding
     * @return JsonResponse 204 No Content
     */
    public function destroy(Hydrometer $hydrometer): JsonResponse
    {
        $this->service->delete($hydrometer);

        return response()->json(null, 204);
    }

    /**
     * Lista as leituras de um hidrômetro específico com paginação.
     *
     * @param  Hydrometer  $hydrometer  Resolvido via Route Model Binding
     * @return AnonymousResourceCollection Coleção paginada de ReadingResource
     */
    public function readings(Hydrometer $hydrometer): AnonymousResourceCollection
    {
        $readings = $hydrometer->readings()
            ->latest('reading_at')
            ->paginate(20);

        return ReadingResource::collection($readings);
    }

    /**
     * Exporta as leituras de um hidrômetro em formato CSV.
     *
     * @param  Hydrometer  $hydrometer  Resolvido via Route Model Binding
     * @return StreamedResponse Download do arquivo CSV
     */
    public function readingsExport(Hydrometer $hydrometer): StreamedResponse
    {
        $filename = "{$hydrometer->code}_leituras.csv";

        return response()->streamDownload(function () use ($hydrometer) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Data', 'Consumo (m³)'], ';');

            $hydrometer->readings()
                ->orderBy('reading_at')
                ->chunk(500, function ($readings) use ($handle) {
                    foreach ($readings as $reading) {
                        fputcsv($handle, [
                            $reading->reading_at->format('d/m/Y H:i:s'),
                            number_format($reading->value_m3, 3, ',', ''), // Sem separador de milhar para facilitar cálculo
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
