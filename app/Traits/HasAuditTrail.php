<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait HasAuditTrail
 *
 * Adiciona automaticamente o ID do usuário logado nos campos created_by e updated_by.
 * Registra quem criou e quem editou cada registro.
 */
trait HasAuditTrail
{
    /**
     * Boot the HasAuditTrail trait for a model.
     */
    public static function bootHasAuditTrail(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && ! $model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created the record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the name of the user who created the record.
     */
    public function getCreatedByNameAttribute(): ?string
    {
        return $this->createdBy?->name;
    }

    /**
     * Get the name of the user who last updated the record.
     */
    public function getUpdatedByNameAttribute(): ?string
    {
        return $this->updatedBy?->name;
    }

    /**
     * Get audit info for display (formatted).
     */
    public function getAuditInfoAttribute(): array
    {
        return [
            'criado_por' => $this->createdBy?->name ?? 'Sistema',
            'criado_em' => $this->created_at?->format('d/m/Y H:i'),
            'atualizado_por' => $this->updatedBy?->name ?? null,
            'atualizado_em' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
