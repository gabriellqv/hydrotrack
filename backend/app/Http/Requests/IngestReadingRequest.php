<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados de entrada do endpoint de ingestão M2M.
 *
 * Este endpoint recebe leituras enviadas pelo simulador IoT
 * ou por dispositivos reais em campo. A autenticação é feita
 * via API key no header, não via Sanctum.
 */
class IngestReadingRequest extends FormRequest
{
    /**
     * Determina se a request está autorizada.
     *
     * @return bool Sempre true — a autenticação M2M é feita via middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para dados de ingestão IoT.
     *
     * @return array<string, mixed> Array associativo de regras
     */
    public function rules(): array
    {
        return [
            'hydrometer_code' => ['required', 'string', 'exists:hydrometers,code'],
            'value_m3' => ['required', 'numeric', 'min:0'],
            'reading_at' => ['required', 'date', 'before_or_equal:now', 'after_or_equal:'.now()->subDay()->toDateString()],
        ];
    }

    /**
     * Mensagens de erro customizadas em pt-BR.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hydrometer_code.exists' => 'Hidrômetro não encontrado com o código informado.',
            'value_m3.min' => 'O valor de consumo não pode ser negativo.',
        ];
    }
}
