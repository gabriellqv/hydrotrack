<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formata a representação JSON de um hidrômetro para a API.
 *
 * Garante que a instância do modelo Hydrometer nunca é exposta
 * diretamente na response, controlando quais campos o frontend recebe.
 */
class HydrometerResource extends JsonResource
{
    /**
     * Transforma o recurso em um array JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'status' => $this->status,
            'type' => $this->type,
            'last_reading_at' => $this->last_reading_at?->toISOString(),
            'readings' => ReadingResource::collection($this->whenLoaded('readings')),
            'chart_data' => $this->when(isset($this->chart_data), $this->chart_data),
            'alerts' => AlertResource::collection($this->whenLoaded('alerts')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
