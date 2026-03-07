<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketSuporte — Ticket de suporte interno (banco central).
 * Permite que inquilinos abram solicitações para o Super Admin.
 */
class TicketSuporte extends Model
{
    protected $table = 'tickets_suporte';

    // Usa a conexão central (não a do tenant)
    protected $connection = 'mysql';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assunto',
        'descricao',
        'prioridade',
        'status',
        'resposta_admin',
        'respondido_em',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
    ];

    // --- Relacionamentos ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class, 'tenant_id');
    }

    // --- Scopes ---

    public function scopeAbertos($query)
    {
        return $query->whereIn('status', ['aberto', 'em_andamento']);
    }

    public function scopeDoTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // --- Helpers ---

    public function isAberto(): bool
    {
        return in_array($this->status, ['aberto', 'em_andamento']);
    }

    public function marcarResolvido(string $resposta): void
    {
        $this->update([
            'status' => 'resolvido',
            'resposta_admin' => $resposta,
            'respondido_em' => now(),
        ]);
    }
}
