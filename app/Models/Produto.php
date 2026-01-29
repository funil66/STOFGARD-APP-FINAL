<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'descricao', 'preco_custo', 'preco_venda', 'estoque_atual', 'unidade'];

    // Relacionamento com o Histórico de Movimentações
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Estoque::class, 'produto_id');
    }
    
    // Calcula estoque atual dinamicamente (opcional, se não salvar no banco)
    public function getEstoqueRealAttribute()
    {
        return $this->movimentacoes()->where('tipo', 'entrada')->sum('quantidade') 
             - $this->movimentacoes()->where('tipo', 'saida')->sum('quantidade');
    }
} 
