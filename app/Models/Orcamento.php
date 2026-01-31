<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;

class Orcamento extends Model implements HasMedia
{
    use HasFactory;
    use HasArquivos;
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
        'valor_final_editado',    // Valor final após edição
        'desconto_prestador',     // Diferença (valor_total - valor_editado)
        'observacoes',
        'extra_attributes',
        'numero_orcamento',
        'comissao_vendedor',
        'comissao_loja',
        'pdf_incluir_pix',        // Controle do botão
        'pix_chave_selecionada',  // Chave escolhida (Crucial)
        'aplicar_desconto_pix',
        'etapa_funil',
    ];

    protected $casts = [
        'data_orcamento' => 'date',
        'data_validade' => 'date',
        'extra_attributes' => 'array',
        'valor_total' => 'decimal:2',
        'valor_final_editado' => 'decimal:2',
        'desconto_prestador' => 'decimal:2',
        'comissao_vendedor' => 'decimal:2',
        'comissao_loja' => 'decimal:2',
        'pdf_incluir_pix' => 'boolean',
        'aplicar_desconto_pix' => 'boolean',
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
        static::creating(function ($model) {
            if (empty($model->numero)) {
                $model->numero = date('Y') . '.' . str_pad(static::max('id') + 1, 4, '0', STR_PAD_LEFT);
            }

            $model->comissao_vendedor = $model->comissao_vendedor ?? 0;
            $model->comissao_loja = $model->comissao_loja ?? 0;

            $model->pdf_incluir_pix = $model->pdf_incluir_pix ?? true;
            $model->aplicar_desconto_pix = $model->aplicar_desconto_pix ?? true;
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
            if ($existe)
                $sequencia++;
        } while ($existe);
        return $numero;
    }
}

