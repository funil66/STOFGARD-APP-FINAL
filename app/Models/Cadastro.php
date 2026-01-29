<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cadastro extends Model
{
    use SoftDeletes;

    // CONFIGURAÇÃO DA BUSCA GLOBAL
    public static $globallySearchableAttributes = ['nome', 'email', 'telefone', 'cpf_cnpj'];

    public function getGlobalSearchResultTitle(): string
    {
        return "Cliente: {$this->nome}";
    }

    public function getGlobalSearchResultDetails(): array
    {
        return [
            'Tipo' => ucfirst($this->tipo),
            'Telefone' => $this->telefone,
        ];
    }

    public function getGlobalSearchResultUrl(): string
    {
        return route('filament.resources.cadastros.edit', $this);
    }

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

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'cadastro_id');
    }
} 
