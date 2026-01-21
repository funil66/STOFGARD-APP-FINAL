<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'grupo',
        'chave',
        'valor',
        'tipo',
        'descricao',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Retorna o valor convertido conforme o tipo
     */
    public function getValorConvertidoAttribute()
    {
        return match ($this->tipo) {
            'boolean' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'number' => (float) $this->valor,
            'json' => json_decode($this->valor, true),
            default => $this->valor,
        };
    }

    /**
     * Scopes para facilitar buscas
     */
    public function scopeGrupo($query, string $grupo)
    {
        return $query->where('grupo', $grupo);
    }

    public function scopeChave($query, string $chave)
    {
        return $query->where('chave', $chave);
    }
}
