<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.paypal.client_id', '');
        $this->clientSecret = (string) config('services.paypal.client_secret', '');
        $this->baseUrl = (string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
    }

    public function criarCheckoutAssinatura(array $dados): array
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('PayPal não configurado. Defina PAYPAL_CLIENT_ID e PAYPAL_CLIENT_SECRET.');
        }

        $token = $this->obterAccessToken();
        $returnUrl = $dados['return_url'] ?? rtrim((string) config('app.url'), '/') . '/login?paypal=approved';
        $cancelUrl = $dados['cancel_url'] ?? rtrim((string) config('app.url'), '/') . '/login?paypal=cancelled';

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) ($dados['reference_id'] ?? ''),
                    'description' => (string) ($dados['description'] ?? 'Assinatura AUTONOMIA ILIMITADA'),
                    'amount' => [
                        'currency_code' => (string) ($dados['currency'] ?? 'BRL'),
                        'value' => number_format((float) ($dados['value'] ?? 0), 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'AUTONOMIA ILIMITADA',
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Falha ao criar checkout PayPal: ' . $response->body());
        }

        $data = $response->json();

        $approveLink = collect($data['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (!$approveLink) {
            throw new RuntimeException('PayPal retornou checkout sem URL de aprovação.');
        }

        return [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'checkout_url' => $approveLink,
        ];
    }

    private function obterAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Falha ao autenticar no PayPal: ' . $response->body());
        }

        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('PayPal não retornou access token válido.');
        }

        return $token;
    }
}
