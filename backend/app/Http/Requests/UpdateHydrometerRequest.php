<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os dados de entrada para atualização de um hidrômetro existente.
 *
 * Todas as regras são idênticas ao StoreHydrometerRequest, exceto que
 * o campo `code` ignora a unicidade do próprio registro sendo editado.
 */
class UpdateHydrometerRequest extends FormRequest
{
    /**
     * Determina se o usuário tem autorização para fazer esta request.
     *
     * @return bool Sempre true, pois a autorização é feita via middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação aplicadas aos dados de entrada.
     *
     * @return array<string, mixed> Array associativo de regras
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('hydrometers')->ignore($this->hydrometer)],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'string', 'max:255'],
            'neighborhood' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', Rule::in(['residential', 'commercial', 'industrial'])],
        ];
    }
}
