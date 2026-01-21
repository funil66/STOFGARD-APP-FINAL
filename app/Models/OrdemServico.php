<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdemServico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ordens_servico';

    protected $fillable = [
        'numero_os',
        'cadastro_id',
        'orcamento_id',
        'tipo_servico',
        'descricao_servico',
        'data_abertura',
        'data_prevista',
        'data_conclusao',
        'status',
        // 'parceiro_id',
        'agenda_id',
        'numero_pedido_parceiro',
        'comissao_parceiro',
        'percentual_comissao_os',
        'valor_servico',
        'valor_produtos',
        'valor_desconto',
        'valor_total',
        'forma_pagamento',
        'pagamento_realizado',
        'dias_garantia',
        'data_fim_garantia',
        'fotos_antes',
        'fotos_depois',
        'observacoes',
        'observacoes_internas',
        'produtos_utilizados',
        'avaliacao',
        'comentario_cliente',
        'criado_por',
        'atualizado_por',
        'assinatura_cliente_path',
        // New legacy/unified cadastro fields
        'cliente_id',
        'parceiro_id',
    ];

    protected $casts = [
        'data_abertura' => 'date',
        'data_prevista' => 'date',
        'data_conclusao' => 'date',
        'data_fim_garantia' => 'date',
        'valor_servico' => 'decimal:2',
        'valor_produtos' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'comissao_parceiro' => 'decimal:2',
        'percentual_comissao_os' => 'decimal:2',
        'pagamento_realizado' => 'boolean',
        'fotos_antes' => 'array',
        'fotos_depois' => 'array',
        'produtos_utilizados' => 'array',
    ];


    // Unified Cadastro Accessor
    public function getCadastroAttribute()
    {
        if (!$this->cadastro_id) {
            return null;
        }
        if (str_starts_with($this->cadastro_id, 'cliente_')) {
            $id = (int)str_replace('cliente_', '', $this->cadastro_id);
            return Cliente::find($id);
        }
        if (str_starts_with($this->cadastro_id, 'parceiro_')) {
            $id = (int)str_replace('parceiro_', '', $this->cadastro_id);
            return Parceiro::find($id);
        }
        return null;
    }

    // Mutators
    public function setCadastroIdAttribute($value)
    {
        $this->attributes['cadastro_id'] = $value;

        if (! $value) {
            return;
        }

        // Only set legacy ids if they are not already provided
        if (str_starts_with($value, 'cliente_')) {
            $id = (int) str_replace('cliente_', '', $value);
            if (! isset($this->attributes['cliente_id']) || $this->attributes['cliente_id'] === null) {
                $this->attributes['cliente_id'] = $id;
            }
        }

        if (str_starts_with($value, 'parceiro_')) {
            $id = (int) str_replace('parceiro_', '', $value);
            if (! isset($this->attributes['parceiro_id']) || $this->attributes['parceiro_id'] === null) {
                $this->attributes['parceiro_id'] = $id;
            }
        }
    }

    // Relationships
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function itens()
    {
        return $this->hasMany(OrdemServicoItem::class, 'ordem_servico_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'ordem_servico_id');
    }

    public function garantia()
    {
        return $this->hasOne(Garantia::class);
    }

    public function garantias()
    {
        return $this->hasMany(Garantia::class);
    }

    // Accessors
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'aberta' => 'info',
            'em_andamento' => 'warning',
            'aguardando_pecas' => 'danger',
            'concluida' => 'success',
            'cancelada' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aberta' => 'Aberta',
            'em_andamento' => 'Em Andamento',
            'aguardando_pecas' => 'Aguardando Peças',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada',
            default => $this->status,
        };
    }

    public function getDiasAbertoAttribute(): int
    {
        if ($this->data_conclusao) {
            return $this->data_abertura->diffInDays($this->data_conclusao);
        }

        return $this->data_abertura->diffInDays(now());
    }

    public function getGarantiaAtivaAttribute(): bool
    {
        if (! $this->data_fim_garantia) {
            return false;
        }

        return now()->lte($this->data_fim_garantia);
    }

    public function getDiasGarantiaRestantesAttribute(): ?int
    {
        if (! $this->garantia_ativa) {
            return null;
        }

        return now()->diffInDays($this->data_fim_garantia);
    }

    // Métodos auxiliares
    public static function gerarNumeroOS(): string
    {
        $ano = date('Y');

        // Buscar pelo maior numero_os do ano (incluindo soft deleted)
        $ultimaOS = self::withTrashed()
            ->where('numero_os', 'like', "OS{$ano}%")
            ->orderBy('numero_os', 'desc')
            ->first();

        $numero = $ultimaOS ? intval(substr($ultimaOS->numero_os, -4)) + 1 : 1;

        return sprintf('OS%s%04d', $ano, $numero);
    }

    public function calcularValorTotal(): void
    {
        $this->valor_total = ($this->valor_servico + $this->valor_produtos) - $this->valor_desconto;
        $this->save();
    }

    public function concluir(): void
    {
        $this->status = 'concluida';
        $this->data_conclusao = now();

        if ($this->dias_garantia > 0) {
            $this->data_fim_garantia = now()->addDays($this->dias_garantia);
        }

        $this->save();
    }

    public function cancelar(): void
    {
        $this->status = 'cancelada';
        $this->save();
    }

    public function getAssinaturaUrlAttribute(): ?string
    {
        if (! $this->assinatura_cliente_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->assinatura_cliente_path);
    }
}
