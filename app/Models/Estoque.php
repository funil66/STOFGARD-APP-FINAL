<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $table = 'estoques'; // Garante que use a tabela certa

    protected $fillable = [
        'produto_id',
        'tipo', // entrada, saida
        'quantidade',
        'motivo',
        'criado_por',
        'data_movimento'
    ];

    protected $casts = [
        'data_movimento' => 'datetime',
    ];

    public function produto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
} 
