<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;
use App\Traits\HasAuditTrail;

class Agenda extends Model implements HasMedia, \OwenIt\Auditing\Contracts\Auditable
{
    use HasArquivos, HasAuditTrail, \OwenIt\Auditing\Auditable;

    protected $table = 'agendas';

    // ATIVA A BUSCA NO TOPO
    public static $globallySearchableAttributes = ['titulo', 'descricao', 'local'];

    protected $fillable = [
        'titulo',
        'descricao',
        'data_hora_inicio',
        'data_hora_fim',
        'dia_inteiro',
        'tipo',
        'status',
        'cliente_id',
        'cadastro_id',
        'ordem_servico_id',
        'orcamento_id',
        'id_parceiro',
        'local',
        'endereco_completo',
        'lembrete_enviado',
        'minutos_antes_lembrete',
        'cor',
        'google_event_id',
        'observacoes',
        'criado_por',
        'atualizado_por',
        'extra_attributes',
    ];

    /**
     * Cadastro vinculado (Cliente, Loja ou Vendedor).
     */
    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    public function getCadastroUrlAttribute(): ?string
    {
        $cad = $this->cadastro;
        if (!$cad) {
            return null;
        }

        return \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $cad]);
    }

    public function getEnderecoMapsAttribute(): ?string
    {
        $end = $this->endereco_completo ?: $this->local;
        if (!$end) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($end);
    }

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'dia_inteiro' => 'boolean',
        'lembrete_enviado' => 'boolean',
        'minutos_antes_lembrete' => 'integer',
        'extra_attributes' => 'array',
    ];

    // Relacionamentos
    // NOTE: cliente() is an alias for backwards compat, prefer cadastro()
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'cliente_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    // Métodos auxiliares
    public function duracao(): int
    {
        return $this->data_hora_inicio->diffInMinutes($this->data_hora_fim);
    }

    public function estaEmAndamento(): bool
    {
        return $this->status === 'em_andamento';
    }

    public function foiConcluido(): bool
    {
        return $this->status === 'concluido';
    }

    public function foiCancelado(): bool
    {
        return $this->status === 'cancelado';
    }

    public function estaPassado(): bool
    {
        return $this->data_hora_fim < now();
    }

    public function estaHoje(): bool
    {
        return $this->data_hora_inicio->isToday();
    }

    public function estaFuturo(): bool
    {
        return $this->data_hora_inicio > now();
    }

    public function getGlobalSearchResultTitle(): string
    {
        return "Agenda: {$this->titulo}";
    }
}
