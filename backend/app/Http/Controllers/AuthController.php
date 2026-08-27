<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Controller de autenticação via Sanctum.
 *
 * Gerencia registro, login, logout e consulta de dados do
 * usuário autenticado. Utiliza tokens pessoais do Sanctum
 * para autenticação stateless da SPA.
 */
class AuthController extends Controller
{
    /**
     * Registra um novo usuário no sistema.
     *
     * @param  Request  $request  Dados: name, email, password, password_confirmation
     * @return JsonResponse 201 Created com token de acesso
     */
    public function register(Request $request): JsonResponse
    {
        if (! config('auth.registration_enabled', false)) {
            return response()->json([
                'message' => 'Registro publico esta desabilitado.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Autentica um usuário e retorna um token Sanctum.
     *
     * @param  Request  $request  Dados: email, password
     * @return JsonResponse Token de acesso e dados do usuário
     *
     * @throws ValidationException Quando as credenciais são inválidas
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        // Revoga tokens anteriores para evitar acumulação na tabela personal_access_tokens
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Revoga o token atual do usuário (logout).
     *
     * @return JsonResponse 200 com mensagem de confirmação
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Retorna os dados do usuário autenticado.
     *
     * @return JsonResponse Dados do usuário logado
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
