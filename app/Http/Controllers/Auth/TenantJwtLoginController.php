<?php
// ARQUIVO: app/Http/Controllers/Auth/TenantJwtLoginController.php
// DESCRIÇÃO: Controlador que emite o "Passaporte" blindado pro Prestador

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantJwtLoginController extends Controller
{
    public function login(Request $request)
    {
        // Regra de segurança básica. Valida a porra dos dados antes de bater no banco.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Autentica via Guard de API do Laravel (assumindo que você instalou JWT-Auth ou Sanctum)
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Credenciais inválidas. Tenta de novo, amigão.'], 401);
        }

        $user = auth('api')->user();

        // O PULO DO GATO: Se o usuário não tiver um cadastro_id, fodeu. Bloqueia.
        if (!$user->cadastro_id) {
            auth('api')->logout();
            return response()->json(['error' => 'Usuário órfão sem empresa vinculada. Contate o suporte.'], 403);
        }

        // Retorna o token pro Frontend (React/Vue/Filament) guardar no LocalStorage ou Cookie HttpOnly
        return $this->respondWithToken($token, $user);
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

    protected function respondWithToken($token, $user)
    {
        // Customizamos o Payload para ter certeza que o Frontend sabe quem é o dono do Token
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->cadastro_id,
                'role' => $user->role ?? 'admin',
            ]
        ]);
    }
}
