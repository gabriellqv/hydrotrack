<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que restringe o acesso a rotas exclusivas de administradores.
 *
 * Verifica o campo `role` do usuário autenticado via Sanctum.
 * Retorna 403 Forbidden se o usuário não for admin.
 */
class EnsureIsAdmin
{
    /**
     * Verifica se o usuário autenticado possui perfil de administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Acesso negado. Apenas administradores podem realizar esta ação.',
            ], 403);
        }

        return $next($request);
    }
}
