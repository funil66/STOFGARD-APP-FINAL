<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TenantJwtLoginController extends Controller
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $this->ensureProviderHost($request);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        if (!$user->tenant_id) {
            return response()->json([
                'message' => 'Usuário sem tenant associado.',
            ], 403);
        }

        $role = $user->role ?: ($user->is_admin ? 'admin' : 'user');

        $token = $this->jwtService->encode([
            'userId' => $user->id,
            'tenantId' => $user->tenant_id,
            'role' => $role,
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => (int) config('domain_routing.jwt.ttl_minutes', 480) * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenantId' => $user->tenant_id,
                'role' => $role,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->attributes->get('auth.user'),
            'tenant_id' => $request->attributes->get('tenant_id'),
        ]);
    }

    private function ensureProviderHost(Request $request): void
    {
        $host = strtolower($request->getHost());
        $providerHost = strtolower(config('domain_routing.provider_subdomain', 'app') . '.' . config('domain_routing.base_domain', 'autonomia.app.br'));

        if ($host !== $providerHost) {
            abort(403, 'Login permitido apenas em ' . $providerHost);
        }
    }
}
