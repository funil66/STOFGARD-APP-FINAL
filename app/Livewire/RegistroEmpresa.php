<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AsaasService;
use App\Services\EmailCodeService;
use App\Services\PayPalService;
use App\Services\TenantTemplateProvisioner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

/**
 * Multi-step company registration form.
 *
 * Step 1: Company data (name, CNPJ, email, phone)
 * Step 2: Admin user (name, email, password)
 * Step 3: Plan selection
 * Step 4: Email verification code
 * Step 5: Confirmation
 */
class RegistroEmpresa extends Component
{
    public int $step = 1;

    // Step 1
    public string $empresa_nome = '';
    public string $empresa_cnpj = '';
    public string $empresa_email = '';
    public string $empresa_telefone = '';

    // Step 2
    public string $admin_nome = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';

    // Step 3
    public string $plano = 'pro';
    public string $metodo_pagamento = 'CREDIT_CARD';

    // Step 4
    public string $email_codigo = '';
    public bool $email_verificado = false;
    public bool $email_codigo_enviado = false;
    public ?string $email_codigo_mensagem = null;

    // Result
    public bool $concluido = false;
    public string $dominio_criado = '';
    public ?string $checkout_url = null;
    public ?string $boleto_url = null;
    public ?string $pix_copia_cola = null;
    public ?string $status_assinatura_url = null;
    public ?string $erro_assinatura = null;

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'empresa_nome' => 'required|string|max:255',
                'empresa_cnpj' => 'nullable|string|max:20',
                'empresa_email' => 'required|email|max:255',
                'empresa_telefone' => 'nullable|string|max:20',
            ],
            2 => [
                'admin_nome' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_password' => 'required|string|min:8|confirmed',
            ],
            3 => [
                'plano' => 'required|in:start,pro,elite,free',
                'metodo_pagamento' => 'required|in:CREDIT_CARD,PIX,BOLETO,PAYPAL',
            ],
            4 => [
                'email_codigo' => 'required|digits:6',
            ],
            default => [],
        };
    }

    public function mount(): void
    {
        $planFromUrl = strtolower((string) request()->query('plano', ''));
        $aliases = [
            'free' => 'start',
            'start' => 'start',
            'pro' => 'pro',
            'elite' => 'elite',
        ];

        if (isset($aliases[$planFromUrl])) {
            $this->plano = $aliases[$planFromUrl];
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 3) {
            $this->validate();
            $this->enviarCodigoVerificacao();
            $this->step = 4;

            return;
        }

        if ($this->step === 4) {
            $this->validate();

            if (!$this->validarCodigoVerificacao()) {
                return;
            }

            $this->step = 5;

            return;
        }

        $this->validate();
        $this->step = min($this->step + 1, 5);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function reenviarCodigoVerificacao(): void
    {
        $this->enviarCodigoVerificacao();
    }

    public function confirmar(): void
    {
        if (!$this->email_verificado) {
            $this->addError('email_codigo', 'Valide o código de confirmação de e-mail antes de continuar.');
            $this->step = 4;

            return;
        }

        $planoSelecionado = $this->plano === 'free' ? 'start' : $this->plano;

        $slug = Str::slug($this->empresa_nome);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $trialDays = (int) env('TRIAL_DAYS', 14);

        // Create tenant (triggers CreateDatabase + MigrateDatabase + SeedDatabase via TenancyServiceProvider)
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => $this->empresa_nome,
            'slug' => $slug,
            'plan' => $planoSelecionado,
            'is_active' => true,
            'status_pagamento' => 'trial',
            'trial_termina_em' => now()->addDays($trialDays),
            'max_users' => match ($planoSelecionado) {
                'start' => 3,
                'pro' => 10,
                'elite' => 999,
                default => 5,
            },
            'max_orcamentos_mes' => match ($planoSelecionado) {
                'start' => (int) env('PLAN_START_OS_LIMIT', env('PLAN_FREE_OS_LIMIT', 30)),
                default => 0, // unlimited
            },
            'limite_os_mes' => match ($planoSelecionado) {
                'start' => (int) env('PLAN_START_OS_LIMIT', env('PLAN_FREE_OS_LIMIT', 30)),
                'pro' => (int) env('PLAN_PRO_OS_LIMIT', 0),
                'elite' => (int) env('PLAN_ELITE_OS_LIMIT', 0),
                default => 30,
            },
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
                'currency' => 'BRL',
                'locale' => 'pt_BR',
            ],
        ]);

        // Create domain (custom domain has priority)
        $baseDomain = (string) config('domain_routing.base_domain', parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');
        $domain = $this->resolveDomain($slug, $baseDomain);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        // Garante baseline visual/funcional idêntico ao tenant referência (STOFGARD)
        app(TenantTemplateProvisioner::class)->apply($tenant);

        // Create admin user inside tenant context
        $tenant->run(function () {
            User::create([
                'name' => $this->admin_nome,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'is_admin' => true,
                'tenant_id' => tenant('id'),
            ]);
        });

        $this->iniciarCobrancaAutomatica($tenant, $planoSelecionado);
        $this->status_assinatura_url = URL::temporarySignedRoute(
            'assinatura.status',
            now()->addDays(7),
            ['tenant' => $tenant->id],
            false,
        );

        $this->dominio_criado = $domain;
        $this->concluido = true;
    }

    private function iniciarCobrancaAutomatica(Tenant $tenant, string $plano): void
    {
        $valorPlano = $this->valorPlano($plano);

        if ($valorPlano <= 0) {
            return;
        }

        if ($this->metodo_pagamento === 'PAYPAL') {
            $this->iniciarCheckoutPayPal($tenant, $plano, $valorPlano);

            return;
        }

        try {
            $asaas = app(AsaasService::class);

            $cliente = $asaas->buscarClientePorReferencia($tenant->id);

            if (!$cliente) {
                $cliente = $asaas->criarCliente([
                    'name' => $this->empresa_nome,
                    'email' => $this->empresa_email,
                    'cpf_cnpj' => $this->empresa_cnpj,
                    'phone' => $this->empresa_telefone,
                    'tenant_id' => $tenant->id,
                ]);
            }

            $assinatura = $asaas->criarAssinatura(
                customerId: $cliente['id'],
                valor: $valorPlano,
                plano: strtoupper($plano),
                billingType: $this->metodo_pagamento,
            );

            $tenant->update([
                'gateway_customer_id' => $cliente['id'] ?? null,
                'gateway_subscription_id' => $assinatura['id'] ?? null,
                'data_vencimento' => $assinatura['nextDueDate'] ?? null,
            ]);

            $primeiroPagamento = [];

            if (!empty($assinatura['id'])) {
                $pagamentos = $asaas->listarPagamentosAssinatura($assinatura['id']);
                $primeiroPagamento = $pagamentos['data'][0] ?? [];
            }

            $this->checkout_url = $primeiroPagamento['invoiceUrl']
                ?? $assinatura['invoiceUrl']
                ?? null;

            $this->boleto_url = $primeiroPagamento['bankSlipUrl']
                ?? null;

            $this->pix_copia_cola = $primeiroPagamento['pixQrCode']
                ?? $primeiroPagamento['pixCopyPaste']
                ?? null;
        } catch (Throwable $e) {
            Log::error('[RegistroEmpresa] Falha ao iniciar cobrança automática', [
                'tenant_id' => $tenant->id,
                'plan' => $plano,
                'billing_type' => $this->metodo_pagamento,
                'error' => $e->getMessage(),
            ]);

            $this->erro_assinatura = 'A empresa foi criada, mas não conseguimos iniciar a cobrança automática agora. Nossa equipa comercial vai concluir contigo.';
        }
    }

    private function iniciarCheckoutPayPal(Tenant $tenant, string $plano, float $valor): void
    {
        try {
            $paypal = app(PayPalService::class);

            $checkout = $paypal->criarCheckoutAssinatura([
                'reference_id' => $tenant->id,
                'description' => 'AUTONOMIA ILIMITADA - Plano ' . strtoupper($plano),
                'value' => $valor,
                'currency' => 'BRL',
                'return_url' => rtrim((string) config('app.url'), '/') . '/login?paypal=approved',
                'cancel_url' => rtrim((string) config('app.url'), '/') . '/login?paypal=cancelled',
            ]);

            $tenant->update([
                'gateway_subscription_id' => $checkout['id'] ?? null,
            ]);

            $this->checkout_url = $checkout['checkout_url'] ?? null;
        } catch (Throwable $e) {
            Log::error('[RegistroEmpresa] Falha ao iniciar checkout PayPal', [
                'tenant_id' => $tenant->id,
                'plan' => $plano,
                'error' => $e->getMessage(),
            ]);

            $this->erro_assinatura = 'A empresa foi criada, mas não conseguimos gerar o checkout PayPal agora. Tente novamente em instantes ou escolha outro método.';
        }
    }

    private function enviarCodigoVerificacao(): void
    {
        $this->resetErrorBag('email_codigo');

        $result = app(EmailCodeService::class)->sendCode(
            email: $this->empresa_email,
            purpose: $this->emailVerificationPurpose(),
            ttlMinutes: 15,
            cooldownSeconds: 60,
        );

        $this->email_codigo_enviado = (bool) ($result['success'] ?? false);
        $this->email_codigo_mensagem = (string) ($result['message'] ?? 'Não foi possível enviar o código.');
    }

    private function validarCodigoVerificacao(): bool
    {
        $isValid = app(EmailCodeService::class)->verifyCode(
            email: $this->empresa_email,
            purpose: $this->emailVerificationPurpose(),
            code: $this->email_codigo,
        );

        if (!$isValid) {
            $this->addError('email_codigo', 'Código inválido ou expirado. Solicite um novo código.');

            return false;
        }

        $this->email_verificado = true;
        $this->email_codigo_mensagem = 'E-mail confirmado com sucesso.';

        return true;
    }

    private function emailVerificationPurpose(): string
    {
        return 'registro_empresa:' . Str::lower(trim($this->empresa_email));
    }

    private function valorPlano(string $plano): float
    {
        return match ($plano) {
            'start' => (float) env('PLAN_START_PRICE', env('PLAN_FREE_PRICE', 49)),
            'pro' => (float) env('PLAN_PRO_PRICE', 97),
            'elite' => (float) env('PLAN_ELITE_PRICE', 197),
            default => 0.0,
        };
    }

    protected function resolveDomain(string $slug, string $baseDomain): string
    {
        return $slug . '.' . $baseDomain;
    }

    public function render()
    {
        return view('livewire.registro-empresa')
            ->layout('components.layouts.guest');
    }
}
