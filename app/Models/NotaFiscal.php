<?php

namespace App\Models;
use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaFiscal extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'cliente_id',
        'cadastro_id',
        'ordem_servico_id',
        'numero_nf',
        'serie',
        'tipo',
        'modelo',
        'data_emissao',
        'chave_acesso',
        'protocolo_autorizacao',
        'valor_total',
        'valor_produtos',
        'valor_servicos',
        'valor_desconto',
        'valor_icms',
        'valor_iss',
        'valor_pis',
        'valor_cofins',
        'observacoes',
        'status',
        'xml_path',
        'pdf_path',
        'data_cancelamento',
        'motivo_cancelamento',
    ];

    // Unified Cadastro Accessor
    public function getCadastroAttribute()
    {
        if (! $this->cadastro_id) {
            return null;
        }

        if (str_starts_with($this->cadastro_id, 'cliente_')) {
            $id = (int) str_replace('cliente_', '', $this->cadastro_id);
            return \App\Models\Cliente::find($id);
        }

        if (str_starts_with($this->cadastro_id, 'parceiro_')) {
            $id = (int) str_replace('parceiro_', '', $this->cadastro_id);
            return \App\Models\Parceiro::find($id);
        }

        return null;
    }

    protected $casts = [
        'data_emissao' => 'date',
        'data_cancelamento' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_produtos' => 'decimal:2',
        'valor_servicos' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'valor_iss' => 'decimal:2',
        'valor_pis' => 'decimal:2',
        'valor_cofins' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }
}
