<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: Tenant — Empresa/cliente do SaaS Stofgard.
 *
 * Cada Tenant representa uma empresa que usa o sistema.
 * Todos os dados de negócio (Cadastro, Orcamento, OS, etc.) pertencem a um Tenant.
 *
 * ISOLAMENTO: via TenantScope + coluna tenant_id em todas as tabelas de negócio.
 *
 * @property int    $id
 * @property string $name         Nome da empresa
 * @property string $slug         Identificador único (usado no subdomain: slug.stofgard.com.br)
 * @property string $plan         Plano contratado: free | starter | pro | enterprise
 * @property bool   $is_active    Se false, acesso ao painel é bloqueado
 * @property array  $settings     Configurações específicas do tenant (JSONB)
 * @property string|null $domain  Domínio customizado (e.g. "app.minha-empresa.com.br")
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'is_active',
        'settings',
        'domain',
        'max_users',
        'max_orcamentos_mes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'max_users' => 'integer',
        'max_orcamentos_mes' => 'integer',
    ];

    // ──────────────────────────────────────────
    // Relacionamentos
    // ──────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    public static function findByDomain(string $domain): ?self
    {
        // Remove porta se houver (localhost:8000)
        $host = explode(':', $domain)[0];

        return static::where('domain', $host)
            ->orWhere('slug', explode('.', $host)[0] ?? null)
            ->where('is_active', true)
            ->first();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Tenant "padrão" para dados legados (anterior ao multi-tenancy).
     * Garante que dados migrados tenham um tenant válido.
     */
    public static function default(): ?self
    {
        return static::where('slug', 'default')->first();
    }
}
