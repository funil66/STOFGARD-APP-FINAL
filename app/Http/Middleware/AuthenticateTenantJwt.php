<?php

namespace App\Http\Middleware;

use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantJwt
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if (!$token) {
            return response()->json([
                'message' => 'Token Bearer ausente.',
            ], 401);
        }

        try {
            $payload = $this->jwtService->decodeAndValidate($token);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Token inválido.',
                'error' => $exception->getMessage(),
            ], 401);
        }

        if (!isset($payload['tenantId'], $payload['role'], $payload['userId'])) {
            return response()->json([
                'message' => 'Token sem claims obrigatórias (tenantId, role, userId).',
            ], 401);
        }

        $request->attributes->set('jwt.payload', $payload);
        $request->attributes->set('tenant_id', $payload['tenantId']);

        $request->attributes->set('auth.user', [
            'id' => $payload['userId'],
            'tenantId' => $payload['tenantId'],
            'role' => $payload['role'],
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
        ]);

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }
}
