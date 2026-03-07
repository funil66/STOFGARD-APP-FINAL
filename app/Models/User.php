<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\MediaLibrary\HasMedia;

use App\Traits\HasArquivos;

class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasArquivos;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_cliente',
        'is_super_admin',
        'tenant_id',
        'cadastro_id',
        'last_login_at',
        'role',               // Fase 3: dono | funcionario | secretaria
        'acesso_financeiro',  // Fase 3: controle granular de permissão
        'local_estoque_id',   // Fase 2.2: multi-almoxarifado
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_cliente' => 'boolean',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'acesso_financeiro' => 'boolean',
        ];
    }

    // === HELPERS DE ROLE (Fase 3) ===

    public function isDono(): bool
    {
        return $this->role === 'dono' || (bool) $this->is_super_admin;
    }

    public function isSecretaria(): bool
    {
        return $this->role === 'secretaria';
    }

    public function isFuncionario(): bool
    {
        return $this->role === 'funcionario';
    }

    public function temAcessoFinanceiro(): bool
    {
        return $this->isDono() || (bool) $this->acesso_financeiro;
    }

    public function cadastro(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    public function localEstoque(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LocalEstoque::class, 'local_estoque_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'super-admin') {
            return $this->is_admin && ($this->is_super_admin ?? false);
        }

        if ($panel->getId() === 'admin') {
            return !$this->is_cliente;
        }

        if ($panel->getId() === 'cliente') {
            if (!$this->is_cliente) {
                return false;
            }

            // Verifica se o tenant atual permite portal do cliente (PRO/ELITE)
            $tenant = $this->tenant ?? tenancy()->tenant;
            if ($tenant && !$tenant->temAcessoPremium()) {
                return false;
            }

            return true;
        }

        return true;
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
