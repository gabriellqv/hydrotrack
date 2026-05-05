<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de autenticação M2M via API key.
 *
 * Valida o header X-API-Key contra o valor configurado em
 * services.ingest.api_key. Usa hash_equals() para prevenir
 * timing attacks na comparação de strings.
 */
class EnsureValidApiKey
{
    /**
     * Valida a API key no header da requisição.
     *
     * @param  Request  $request  Requisição HTTP com header X-API-Key
     * @param  Closure  $next  Próximo middleware na pipeline
     * @return Response 401 se a key for inválida ou ausente
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $configuredKey = config('services.ingest.api_key');

        if (! $apiKey || ! $configuredKey || ! hash_equals($configuredKey, $apiKey)) {
            return response()->json([
                'message' => 'API key inválida ou ausente.',
            ], 401);
        }

        return $next($request);
    }
}
