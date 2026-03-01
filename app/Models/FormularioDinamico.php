<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FormularioDinamico — Formulários customizáveis criados pelo tenant.
 * Campos armazenados como JSON (Filament Builder format).
 */
class FormularioDinamico extends Model
{
    use SoftDeletes;

    protected $table = 'formularios_dinamicos';

    protected $fillable = [
        'nome',
        'tipo_servico',
        'campos',
        'ativo',
        'descricao',
    ];

    protected $casts = [
        'campos' => 'array',
        'ativo' => 'boolean',
    ];

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopePorTipoServico($query, string $tipo)
    {
        return $query->where('tipo_servico', $tipo)->orWhereNull('tipo_servico');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Retorna os nomes dos campos do formulário (para listagem).
     */
    public function getNomesCamposAttribute(): array
    {
        return collect($this->campos ?? [])
            ->pluck('data.label')
            ->filter()
            ->values()
            ->toArray();
    }
}
