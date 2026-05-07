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

/**
 * Controller de hidrômetros.
 *
 * Responsável exclusivamente por receber requests HTTP, delegar ao
 * HydrometerService e formatar a response via HydrometerResource.
 * Zero lógica de negócio aqui — apenas I/O.
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
     * @param  Hydrometer  $hydrometer  Resolvido automaticamente via Route Model Binding
     */
    public function show(Hydrometer $hydrometer): HydrometerResource
    {
        return new HydrometerResource($hydrometer->load(['readings' => function ($q) {
            $q->latest('reading_at')->limit(10);
        }]));
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
}
