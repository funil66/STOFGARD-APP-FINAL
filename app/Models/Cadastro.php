<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cadastro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'tipo', // cliente, loja, vendedor, arquiteto
        'parent_id', // Para vincular vendedor a loja
        'documento', // CPF/CNPJ
        'rg_ie',
        'email',
        'telefone',
        'telefone_fixo',
        'cep',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'comissao_percentual',
    ];

    protected $casts = [
        'comissao_percentual' => 'decimal:2',
    ];

    // Relacionamento: Vendedor pertence a uma Loja
    public function loja(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'parent_id')->where('tipo', 'loja');
    }

    // Relacionamento: Loja tem muitos Vendedores
    public function vendedores(): HasMany
    {
        return $this->hasMany(Cadastro::class, 'parent_id')->where('tipo', 'vendedor');
    }

    // Relacionamento: Cadastro tem muitos Orçamentos
    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'cadastro_id');
    }
}
