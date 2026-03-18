<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    public function encode(array $payload, ?int $ttlMinutes = null): string
    {
        $now = time();
        $ttl = $ttlMinutes ?? (int) config('domain_routing.jwt.ttl_minutes', 480);

        $registered = [
            'iss' => config('domain_routing.jwt.issuer', config('app.name', 'autonomia-app')),
            'iat' => $now,
            'exp' => $now + ($ttl * 60),
            'jti' => Str::uuid()->toString(),
        ];

        $claims = array_merge($registered, $payload);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret(), true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
    }

    public function decodeAndValidate(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new RuntimeException('JWT malformado.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('JWT inválido.');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('Algoritmo JWT não suportado.');
        }

        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret(), true);
        $expectedEncoded = $this->base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedEncoded, $encodedSignature)) {
            throw new RuntimeException('Assinatura JWT inválida.');
        }

        $now = time();
        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp < $now) {
            throw new RuntimeException('JWT expirado.');
        }

        return $payload;
    }

    private function secret(): string
    {
        $secret = (string) config('domain_routing.jwt.secret', '');

        if ($secret !== '') {
            return $secret;
        }

        $appKey = (string) config('app.key', '');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        if ($appKey !== '') {
            return $appKey;
        }

        throw new RuntimeException('JWT secret não configurado. Defina JWT_SECRET no ambiente.');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Base64URL inválido no JWT.');
        }

        return $decoded;
    }
}
