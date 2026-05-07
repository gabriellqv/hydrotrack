<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os dados de entrada para criação de um novo hidrômetro.
 *
 * Garante que o código é único, as coordenadas GPS são válidas
 * e o tipo do imóvel pertence ao enum permitido.
 */
class StoreHydrometerRequest extends FormRequest
{
    /**
     * Determina se o usuário tem autorização para fazer esta request.
     *
     * @return bool Sempre true — a autorização é feita via middleware.
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
            'code' => ['required', 'string', 'max:20', 'unique:hydrometers,code'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['residential', 'commercial', 'industrial'])],
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
            'code.unique' => 'Já existe um hidrômetro com este código.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180.',
        ];
    }
}
