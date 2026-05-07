<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formata a representação JSON de uma leitura de consumo para a API.
 */
class ReadingResource extends JsonResource
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
            'hydrometer_id' => $this->hydrometer_id,
            'value_m3' => (float) $this->value_m3,
            'reading_at' => $this->reading_at->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
