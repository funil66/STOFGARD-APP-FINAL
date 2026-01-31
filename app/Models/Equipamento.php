<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;

class Equipamento extends Model implements HasMedia
{
    use SoftDeletes, HasArquivos;

    protected $fillable = [
        'nome',
        'descricao',
        'codigo_patrimonio',
        'status',
        'data_aquisicao',
        'valor_aquisicao',
        'localizacao',
        'observacoes',
        'criado_por',
    ];

    protected $casts = [
        'data_aquisicao' => 'date',
        'valor_aquisicao' => 'decimal:2',
    ];

    // Scopes
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeEmManutencao($query)
    {
        return $query->where('status', 'manutencao');
    }

    public function scopeBaixados($query)
    {
        return $query->where('status', 'baixado');
    }

    // Accessors
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'ativo' => 'success',
            'manutencao' => 'warning',
            'baixado' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ativo' => '✅ Ativo',
            'manutencao' => '🔧 Em Manutenção',
            'baixado' => '❌ Baixado',
            default => $this->status,
        };
    }
}
