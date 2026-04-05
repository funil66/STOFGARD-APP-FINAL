<?php
// ARQUIVO: app/Http/Controllers/Auth/TenantJwtLoginController.php
// DESCRIÇÃO: Controlador que emite o "Passaporte" blindado pro Prestador

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class TenantJwtLoginController extends Controller
{
    public function login(Request $request)
    {
        // Regra de segurança básica. Valida a porra dos dados antes de bater no banco.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $jwtSecret = (string) config('jwt.secret', '');

        if ($jwtSecret === '') {
            $jwtSecret = (string) env('JWT_SECRET', '');

            if ($jwtSecret !== '') {
                config(['jwt.secret' => $jwtSecret]);
            }
        }

        if ($jwtSecret === '') {
            $appKey = (string) config('app.key', '');
            $jwtSecret = $appKey !== '' ? hash('sha256', $appKey) : '';

            if ($jwtSecret !== '') {
                config(['jwt.secret' => $jwtSecret]);
            }
        }

        if ($jwtSecret === '') {
            return response()->json([
                'error' => 'Configuração de autenticação indisponível no momento. Tente novamente em instantes.',
            ], 503);
        }

        // Autentica via Guard de API do Laravel (assumindo que você instalou JWT-Auth ou Sanctum)
        try {
            $token = auth('api')->attempt($credentials);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Falha temporária no login. Tente novamente em alguns segundos.',
            ], 503);
        }

        if (! $token) {
            return response()->json(['error' => 'Credenciais inválidas. Tente novamente.'], 401);
        }

        $user = auth('api')->user();

        $tenantId = $user->cadastro_id ?? $user->tenant_id;

        // O PULO DO GATO: Se o usuário não tiver um cadastro_id, fodeu. Bloqueia.
        if (! $tenantId) {
            auth('api')->logout();
            return response()->json(['error' => 'Usuário órfão sem empresa vinculada. Contate o suporte.'], 403);
        }

        // Retorna o token pro Frontend (React/Vue/Filament) guardar no LocalStorage ou Cookie HttpOnly
        return $this->respondWithToken($token, $user, $tenantId);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Deslogado com sucesso. Tchau e bença.']);
    }

    protected function respondWithToken($token, $user, $tenantId)
    {
        // Customizamos o Payload para ter certeza que o Frontend sabe quem é o dono do Token
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $tenantId,
                'role' => $user->role ?? 'admin',
            ]
        ]);
    }
}
