<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class JwtSessionBridgeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token Bearer ausente.',
            ], 401);
        }

        try {
            $user = auth('api')->setToken($token)->authenticate();
        } catch (Throwable) {
            return response()->json([
                'message' => 'Token inválido ou expirado.',
            ], 401);
        }

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado pelo token.',
            ], 401);
        }

        auth()->guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Sessão web criada com sucesso.',
            'redirect_to' => '/admin',
        ]);
    }
}
