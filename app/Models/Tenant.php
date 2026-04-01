<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use SoftDeletes, HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'plan',
            'is_active',
            'max_users',
            'max_orcamentos_mes',
            'settings',
            // Billing (Fase 1)
            'gateway_customer_id',
            'gateway_subscription_id',
            'status_pagamento',
            'data_vencimento',
            'trial_termina_em',
            'limite_os_mes',
            'os_criadas_mes_atual',
            'data',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'max_users' => 'integer',
        'max_orcamentos_mes' => 'integer',
        'limite_os_mes' => 'integer',
        'os_criadas_mes_atual' => 'integer',
        'data_vencimento' => 'date',
        'trial_termina_em' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Verifica se o tenant está em boa situação de pagamento.
     */
    public function estaEmDia(): bool
    {
        return in_array($this->status_pagamento, ['trial', 'ativo']);
    }

    /**
     * Verifica se o tenant ainda está no período trial.
     */
    public function estaEmTrial(): bool
    {
        return $this->status_pagamento === 'trial'
            && $this->trial_termina_em
            && \Carbon\Carbon::parse($this->trial_termina_em)->isFuture();
    }

    /**
     * Retorna dias restantes do trial (ou null se não estiver em trial).
     */
    public function diasRestantesTrial(): ?int
    {
        if (!$this->estaEmTrial()) {
            return null;
        }

        return (int) now()->diffInDays(\Carbon\Carbon::parse($this->trial_termina_em));
    }

    /**
     * Verifica se o tenant pode criar mais Ordens de Serviço neste mês.
     */
    public function podeCriarOS(): bool
    {
        if ($this->limite_os_mes === 0) {
            return true; // 0 = ilimitado
        }

        return $this->os_criadas_mes_atual < $this->limite_os_mes;
    }

    /**
     * Incrementa o contador de OS criadas no mês.
     */
    public function incrementarOsCriadas(): void
    {
        $this->increment('os_criadas_mes_atual');
    }

    /**
     * Tiers helper methods
     */
    public function isStart(): bool
    {
        return strtolower($this->plan) === 'start';
    }

    public function isPro(): bool
    {
        return strtolower($this->plan) === 'pro';
    }

    public function isElite(): bool
    {
        return strtolower($this->plan) === 'elite';
    }

    /**
     * Verifica se o Tenant tem acesso ao menos ao plano PRO ou superior
     */
    public function temAcessoPremium(): bool
    {
        return in_array(strtolower($this->plan), ['pro', 'elite']);
    }
}
