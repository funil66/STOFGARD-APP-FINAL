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
use App\Traits\HasSequentialNumber;
use App\Traits\HasAuditTrail;

class Orcamento extends Model implements HasMedia
{
    use HasFactory;
    use HasArquivos;
    use SoftDeletes;
    use HasSequentialNumber;
    use HasAuditTrail;

    // Configuração para HasSequentialNumber trait
    protected string $sequenceType = 'orcamento';
    protected string $sequenceColumn = 'numero';

    public static $globallySearchableAttributes = ['numero', 'cadastro.nome'];

    public function getGlobalSearchResultTitle(): string
    {
        return "Orçamento #{$this->numero}";
    }

    public function getGlobalSearchResultUrl(): string
    {
        return route('filament.admin.resources.orcamentos.edit', ['record' => $this->id]);
    }

    protected $fillable = [
        'numero',
        'cadastro_id',
        'vendedor_id',
        'loja_id',
        'id_parceiro',
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
        'pdf_mostrar_fotos',      // Controle de exibição de fotos
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
        'pdf_mostrar_fotos' => 'boolean',
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

    // --- GERAÇÃO DE NÚMERO (LEGADO - MANTIDO PARA RETROCOMPATIBILIDADE) ---
    protected static function booted()
    {
        static::creating(function ($model) {
            // Número é gerado automaticamente pelo HasSequentialNumber trait

            $model->comissao_vendedor = $model->comissao_vendedor ?? 0;
            $model->comissao_loja = $model->comissao_loja ?? 0;

            $model->pdf_incluir_pix = $model->pdf_incluir_pix ?? \App\Models\Setting::get('pdf_include_pix_global', true);
            $model->pdf_mostrar_fotos = $model->pdf_mostrar_fotos ?? \App\Models\Setting::get('pdf_mostrar_fotos_global', true);
            $model->aplicar_desconto_pix = $model->aplicar_desconto_pix ?? \App\Models\Setting::get('pdf_aplicar_desconto_global', true);
        });
    }

    /**
     * Método legado - mantido para retrocompatibilidade
     * Use generateSequentialNumber() do trait HasSequentialNumber
     * 
     * @deprecated Use HasSequentialNumber::generateSequentialNumber()
     * @return string
     */
    public static function gerarNumeroOrcamento(): string
    {
        return self::gerarNumeroSequencial();
    }
}

