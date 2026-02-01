<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo',
        'nome',
        'slug',
        'cor',
        'icone',
        'descricao',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    // Boot para gerar slug automaticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });

        static::updating(function ($categoria) {
            if ($categoria->isDirty('nome') && empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });
    }

    // Scopes
    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    // Relacionamentos
    public function financeiros()
    {
        return $this->hasMany(Financeiro::class, 'categoria_id');
    }
}
