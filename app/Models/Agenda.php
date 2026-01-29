<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;

class Agenda extends Model
{
    use SoftDeletes;

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
        'local',
        'endereco_completo',
        'lembrete_enviado',
        'minutos_antes_lembrete',
        'cor',
        'google_event_id',
        'observacoes',
        'criado_por',
        'atualizado_por',
    ];

    // Unified Cadastro Accessor
    public function getCadastroAttribute()
    {
        if (! $this->cadastro_id) {
            return null;
        }

        if (str_starts_with($this->cadastro_id, 'cliente_')) {
            $id = (int) str_replace('cliente_', '', $this->cadastro_id);
            return Cliente::find($id);
        }

        if (str_starts_with($this->cadastro_id, 'parceiro_')) {
            $id = (int) str_replace('parceiro_', '', $this->cadastro_id);
            return Parceiro::find($id);
        }

        return null;
    }

    public function getCadastroUrlAttribute(): ?string
    {
        $cad = $this->cadastro;
        if (! $cad) {
            return null;
        }

        if ($cad instanceof Cliente) {
            return \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $cad]);
        }

        if ($cad instanceof Parceiro) {
            return \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $cad]);
        }

        return null;
    }

    public function getEnderecoMapsAttribute(): ?string
    {
        $end = $this->endereco_completo ?: $this->local;
        if (! $end) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($end);
    }

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'dia_inteiro' => 'boolean',
        'lembrete_enviado' => 'boolean',
        'minutos_antes_lembrete' => 'integer',
    ];

    // Relacionamentos
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
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
