<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Orcamento extends Model
{
    use SoftDeletes;

    public static $globallySearchableAttributes = ['numero', 'cadastro.nome'];

    public function getGlobalSearchResultTitle(): string
    {
        return "Orçamento #{$this->numero}";
    }

    public function getGlobalSearchResultUrl(): string
    {
        return route('filament.resources.orcamentos.edit', $this);
    }

    protected $fillable = [
        'numero', 
        'cadastro_id', 
        'vendedor_id', 
        'loja_id',     
        'data_orcamento', 
        'data_validade', 
        'status', 
        'valor_total', 
        'observacoes',
        'numero_orcamento',
        'comissao_vendedor',
        'comissao_loja',
        'pdf_incluir_pix',        // Controle do botão
        'pix_chave_selecionada',  // Chave escolhida (Crucial)
    ];

    protected $casts = [
        'data_orcamento' => 'date',
        'data_validade' => 'date',
        'valor_total' => 'decimal:2',
        'comissao_vendedor' => 'decimal:2',
        'comissao_loja' => 'decimal:2',
        'pdf_incluir_pix' => 'boolean',
    ];

    // --- FUNÇÃO QUE FALTAVA (CORREÇÃO DO ERRO) ---
    public function calcularTotal()
    {
        // Recalcula a soma dos itens vinculados
        $total = $this->itens()->sum('subtotal');
        $this->valor_total = $total;
        
        // Salva sem disparar eventos novamente para evitar loop infinito
        $this->saveQuietly();
    }

    // --- RELACIONAMENTOS ---

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cadastro::class, 'cadastro_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cadastro::class, 'vendedor_id');
    }

    public function loja(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cadastro::class, 'loja_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(\App\Models\OrcamentoItem::class, 'orcamento_id');
    }
    
    public function ordemServico(): HasOne
    {
        return $this->hasOne(\App\Models\OrdemServico::class);
    }

    // --- GERAÇÃO DE NÚMERO ---
    protected static function booted()
    {
        static::creating(function ($orcamento) {
            if (empty($orcamento->numero)) {
                $orcamento->numero = self::gerarNumeroOrcamento();
                $orcamento->numero_orcamento = $orcamento->numero;
            }
        });
    }

    public static function gerarNumeroOrcamento(): string
    {
        $ano = date('Y');
        $ultimo = self::withTrashed()->whereYear('created_at', $ano)->latest('id')->first();
        
        $sequencia = 1;
        if ($ultimo && preg_match('/\.(\d+)$/', $ultimo->numero, $matches)) {
            $sequencia = intval($matches[1]) + 1;
        }
        do {
            $numero = $ano . '.' . str_pad($sequencia, 4, '0', STR_PAD_LEFT);
            $existe = self::withTrashed()->where('numero', $numero)->exists();
            if ($existe) $sequencia++;
        } while ($existe);
        return $numero;
    }
}

    