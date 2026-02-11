<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasArquivos;
use App\Traits\HasAuditTrail;
use Spatie\MediaLibrary\HasMedia;

class Cadastro extends Model implements HasMedia, \OwenIt\Auditing\Contracts\Auditable
{
    use HasFactory, SoftDeletes, HasArquivos, HasAuditTrail, \OwenIt\Auditing\Auditable;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-sync: If celular is set but telefone is empty, copy.
            if (!empty($model->celular) && empty($model->telefone)) {
                $model->telefone = $model->celular;
            }
            // Auto-sync: If telefone is set but celular is empty, copy (assuming it's mobile for simplicity).
            if (!empty($model->telefone) && empty($model->celular)) {
                $model->celular = $model->telefone;
            }
        });
    }

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
        return route('filament.admin.resources.cadastros.edit', ['record' => $this->id]);
    }

    protected $fillable = [
        'nome',
        'tipo', // cliente, loja, vendedor, arquiteto, funcionario
        'parent_id', // Para vincular vendedor a loja
        'documento', // CPF/CNPJ
        'rg_ie',
        'email',
        'telefone',
        'celular',
        'telefone_fixo',
        'cep',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'comissao_percentual',
        'pdf_mostrar_documentos',
        // Campos de funcionário
        'cargo',
        'salario_base',
        'data_admissao',
        'data_demissao',
        'is_socio',
        'percentual_prolabore',
    ];

    protected $casts = [
        'comissao_percentual' => 'decimal:2',
        'pdf_mostrar_documentos' => 'boolean',
        'salario_base' => 'decimal:2',
        'data_admissao' => 'date',
        'data_demissao' => 'date',
        'is_socio' => 'boolean',
        'percentual_prolabore' => 'decimal:2',
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

    /**
     * Retorna o endereço completo do cadastro: prioriza `endereco_completo` quando presente,
     * caso contrário monta a string a partir dos campos (logradouro, número, complemento, bairro, cidade, estado, CEP).
     */
    public function formatEnderecoCompleto(): string
    {
        if (!empty($this->endereco_completo)) {
            return $this->endereco_completo;
        }

        return trim(implode(', ', array_filter([
            $this->logradouro,
            $this && ($this->numero ?? false) ? "nº {$this->numero}" : null,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
            $this->cep ? "CEP: {$this->cep}" : null,
        ])));
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'cadastro_id');
    }

    // Relacionamento: Cadastro tem muitos registros Financeiros
    public function financeiros(): HasMany
    {
        return $this->hasMany(Financeiro::class, 'cadastro_id');
    }

    // Relacionamento: Cadastro tem muitos Agendamentos
    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'cadastro_id');
    }

    // ===== ACCESSORS DE RESUMO FINANCEIRO =====

    /**
     * Total de receitas (entradas pagas)
     */
    public function getTotalReceitasAttribute(): float
    {
        return (float) $this->financeiros()
            ->where('tipo', 'entrada')
            ->where('status', 'pago')
            ->sum('valor');
    }

    /**
     * Total de despesas/saídas pagas
     */
    public function getTotalDespesasAttribute(): float
    {
        return (float) $this->financeiros()
            ->where('tipo', 'saida')
            ->where('status', 'pago')
            ->sum('valor');
    }

    /**
     * Saldo (receitas - despesas)
     */
    public function getSaldoAttribute(): float
    {
        return $this->total_receitas - $this->total_despesas;
    }

    /**
     * Total pendente a receber
     */
    public function getPendentesReceberAttribute(): float
    {
        return (float) $this->financeiros()
            ->where('tipo', 'entrada')
            ->where('status', 'pendente')
            ->sum('valor');
    }

    /**
     * Total pendente a pagar
     */
    public function getPendentesPagarAttribute(): float
    {
        return (float) $this->financeiros()
            ->where('tipo', 'saida')
            ->where('status', 'pendente')
            ->sum('valor');
    }

    /**
     * Quantidade de orçamentos aprovados
     */
    public function getOrcamentosAprovadosCountAttribute(): int
    {
        return $this->orcamentos()->where('status', 'aprovado')->count();
    }

    /**
     * Quantidade de OS concluídas
     */
    public function getOsConcluidasCountAttribute(): int
    {
        return $this->ordensServico()->whereIn('status', ['concluida', 'finalizada'])->count();
    }

    // ===== QUERY SCOPES POR TIPO =====

    /**
     * Escopo: Apenas Clientes
     */
    public function scopeClientes($query)
    {
        return $query->where('tipo', 'cliente');
    }

    /**
     * Escopo: Apenas Lojas
     */
    public function scopeLojas($query)
    {
        return $query->where('tipo', 'loja');
    }

    /**
     * Escopo: Apenas Vendedores
     */
    public function scopeVendedores($query)
    {
        return $query->where('tipo', 'vendedor');
    }

    /**
     * Escopo: Apenas Arquitetos
     */
    public function scopeArquitetos($query)
    {
        return $query->where('tipo', 'arquiteto');
    }

    /**
     * Escopo: Todos os parceiros (Lojas, Vendedores, Arquitetos)
     */
    public function scopeParceiros($query)
    {
        return $query->whereIn('tipo', ['loja', 'vendedor', 'arquiteto']);
    }

    /**
     * Escopo: Apenas Funcionários
     */
    public function scopeFuncionarios($query)
    {
        return $query->where('tipo', 'funcionario');
    }

    /**
     * Escopo: Apenas Sócios (funcionários com flag is_socio)
     */
    public function scopeSocios($query)
    {
        return $query->where('tipo', 'funcionario')->where('is_socio', true);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->pdf_mostrar_documentos = $model->pdf_mostrar_documentos ?? \App\Models\Setting::get('pdf_mostrar_documentos_global', true);
        });
    }
}
