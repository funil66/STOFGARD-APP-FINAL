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

class Orcamento extends Model implements HasMedia, \OwenIt\Auditing\Contracts\Auditable
{
    use HasFactory;
    use HasArquivos;
    use SoftDeletes;
    use HasSequentialNumber;
    use HasAuditTrail;
    use \OwenIt\Auditing\Auditable;

    protected static function boot()
    {
        parent::boot();
        self::observe(\OwenIt\Auditing\AuditableObserver::class);
    }

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
        'pdf_mostrar_comissoes',       // #2a: Toggle comissões no PDF
        'pdf_mostrar_parcelamento',    // #2a: Toggle parcelamento no PDF
        'pdf_desconto_pix_percentual', // #2b: Alíquota PIX per-orçamento
        'pdf_parcelamento_custom',     // #2b: Parcelamento customizado per-orçamento
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
        'pdf_mostrar_comissoes' => 'boolean',
        'pdf_mostrar_parcelamento' => 'boolean',
        'pdf_desconto_pix_percentual' => 'decimal:2',
        'pdf_parcelamento_custom' => 'array',
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

    // --- ACESSORES FINANCEIROS CENTRALIZADOS ---

    /**
     * Valor efetivo do orçamento (editado se > 0, senão calculado).
     * Usar em TODAS as exibições: tabela, infolist, PDF.
     */
    public function getValorEfetivoAttribute(): float
    {
        return floatval($this->valor_final_editado) > 0
            ? floatval($this->valor_final_editado)
            : floatval($this->valor_total);
    }

    /**
     * Verifica se o valor foi editado manualmente.
     */
    public function getValorFoiEditadoAttribute(): bool
    {
        return floatval($this->valor_final_editado) > 0;
    }

    /**
     * Calcula o valor com todos os descontos aplicáveis.
     * Retorna array com breakdown completo para uso em views e PDFs.
     *
     * @param float|null $percentualPix  Percentual de desconto PIX (de Settings)
     * @return array{valor_base: float, desconto_prestador: float, desconto_pix: float, percentual_pix: float, valor_final: float, valor_foi_editado: bool}
     */
    public function getValorComDescontos(?float $percentualPix = null): array
    {
        $valorBase = floatval($this->valor_total);
        $foiEditado = $this->valor_foi_editado;

        // Desconto do prestador é SEMPRE lido do model (aplicado tanto manualmente quanto por lógica)
        $descontoPrestador = max(0, floatval($this->desconto_prestador));

        // Valor efetivo = editado se existir, senão valor_total
        $valorEfetivo = $this->valor_efetivo;

        $descontoPix = 0;

        // Desconto PIX só se aplica quando o valor NÃO foi editado manualmente
        if (!$foiEditado && $this->aplicar_desconto_pix && $percentualPix !== null && $percentualPix > 0) {
            $descontoPix = ($valorEfetivo * $percentualPix) / 100;
        }

        $valorFinal = $valorEfetivo - $descontoPix;

        return [
            'valor_base' => $valorBase,
            'desconto_prestador' => $descontoPrestador,
            'desconto_pix' => $descontoPix,
            'percentual_pix' => $percentualPix ?? 0,
            'valor_final' => $valorFinal,
            'valor_foi_editado' => $foiEditado,
        ];
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

    public function financeiros(): HasMany
    {
        return $this->hasMany(\App\Models\Financeiro::class, 'orcamento_id');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(\App\Models\Agenda::class, 'orcamento_id');
    }

    // --- GERAÇÃO DE NÚMERO (LEGADO - MANTIDO PARA RETROCOMPATIBILIDADE) ---
    protected static function booted()
    {
        static::creating(function ($model) {
            // Número é gerado automaticamente pelo HasSequentialNumber trait

            $model->comissao_vendedor = $model->comissao_vendedor ?? 0;
            $model->comissao_loja = $model->comissao_loja ?? 0;
            $model->desconto_prestador = $model->desconto_prestador ?? 0;
            $model->valor_final_editado = $model->valor_final_editado ?? 0;

            $model->pdf_incluir_pix = $model->pdf_incluir_pix ?? \App\Models\Setting::get('pdf_include_pix_global', true);
            $model->pdf_mostrar_fotos = $model->pdf_mostrar_fotos ?? \App\Models\Setting::get('pdf_mostrar_fotos_global', true);
            $model->aplicar_desconto_pix = $model->aplicar_desconto_pix ?? \App\Models\Setting::get('pdf_aplicar_desconto_global', true);
        });

        // Auto-calcula desconto_prestador quando valor_final_editado muda
        static::updating(function ($model) {
            if ($model->isDirty('valor_final_editado')) {
                $valorEditado = floatval($model->valor_final_editado ?? 0);
                $valorTotal = floatval($model->valor_total);
                if ($valorEditado > 0) {
                    $model->desconto_prestador = $valorTotal - $valorEditado;
                } else {
                    $model->desconto_prestador = 0;
                }
            }
        });

        // Propaga para módulos vinculados quando valor mudou
        static::updated(function ($model) {
            if ($model->wasChanged('valor_final_editado') || $model->wasChanged('desconto_prestador')) {
                \App\Services\OrcamentoFormService::sincronizarValorModulos($model);
            }
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

