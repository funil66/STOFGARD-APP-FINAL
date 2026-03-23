<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailCodeService
{
    public function sendCode(string $email, string $purpose, int $ttlMinutes = 15, int $cooldownSeconds = 60): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $cooldownKey = $this->cooldownKey($normalizedEmail, $purpose);
        $remainingCooldown = Cache::get($cooldownKey);

        if (is_numeric($remainingCooldown) && (int) $remainingCooldown > 0) {
            return [
                'success' => false,
                'message' => 'Aguarde alguns segundos antes de solicitar um novo código.',
                'retry_after' => (int) $remainingCooldown,
            ];
        }

        $code = (string) random_int(100000, 999999);
        $payload = [
            'hash' => $this->hashCode($normalizedEmail, $purpose, $code),
            'expires_at' => now()->addMinutes($ttlMinutes)->timestamp,
            'attempts_left' => 5,
            'email' => $normalizedEmail,
            'purpose' => $purpose,
        ];

        Cache::put($this->cacheKey($normalizedEmail, $purpose), $payload, now()->addMinutes($ttlMinutes));
        Cache::put($cooldownKey, $cooldownSeconds, now()->addSeconds($cooldownSeconds));

        $subject = 'Código de confirmação - AUTONOMIA ILIMITADA';
        $body = "Seu código é: {$code}\n\nEste código expira em {$ttlMinutes} minutos.\nSe você não solicitou, ignore este e-mail.";

        Mail::raw($body, function ($message) use ($normalizedEmail, $subject): void {
            $message->to($normalizedEmail)
                ->subject($subject);
        });

        Log::info('[EmailCodeService] Código enviado', [
            'email' => $normalizedEmail,
            'purpose' => $purpose,
            'expires_in_minutes' => $ttlMinutes,
        ]);

        return [
            'success' => true,
            'message' => 'Código enviado para o seu e-mail.',
            'retry_after' => $cooldownSeconds,
        ];
    }

    public function verifyCode(string $email, string $purpose, string $code): bool
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $key = $this->cacheKey($normalizedEmail, $purpose);

        $payload = Cache::get($key);

        if (!is_array($payload)) {
            return false;
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            Cache::forget($key);
            return false;
        }

        $hash = $this->hashCode($normalizedEmail, $purpose, $code);
        $isValid = hash_equals((string) ($payload['hash'] ?? ''), $hash);

        if ($isValid) {
            Cache::forget($key);
            Cache::forget($this->cooldownKey($normalizedEmail, $purpose));
            return true;
        }

        $attemptsLeft = max(0, (int) ($payload['attempts_left'] ?? 0) - 1);

        if ($attemptsLeft <= 0) {
            Cache::forget($key);
            return false;
        }

        $payload['attempts_left'] = $attemptsLeft;
        Cache::put($key, $payload, now()->timestamp < (int) $payload['expires_at']
            ? now()->addSeconds((int) $payload['expires_at'] - now()->timestamp)
            : now()->addMinute());

        return false;
    }

    private function cacheKey(string $email, string $purpose): string
    {
        return 'email_code:' . md5($purpose . '|' . $email);
    }

    private function cooldownKey(string $email, string $purpose): string
    {
        return 'email_code_cooldown:' . md5($purpose . '|' . $email);
    }

    private function hashCode(string $email, string $purpose, string $code): string
    {
        return hash_hmac('sha256', $purpose . '|' . $email . '|' . trim($code), (string) config('app.key'));
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
