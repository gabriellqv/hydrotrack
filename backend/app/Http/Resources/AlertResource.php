<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formata a representação JSON de um alerta para a API.
 */
class AlertResource extends JsonResource
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
            'hydrometer' => new HydrometerResource($this->whenLoaded('hydrometer')),
            'type' => $this->type,
            'message' => $this->message,
            'resolved' => $this->resolved,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
