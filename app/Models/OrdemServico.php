<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory, SoftDeletes;

    // --- CORREÇÃO DO ERRO DE FK ---
    // Define explicitamente o nome da tabela no banco
    protected $table = 'ordens_servico'; 

    protected $fillable = [
        'cadastro_id',
        'orcamento_id',
        'numero_os',
        'status',
        'valor_total',
        'data_inicio',
        'data_fim',
        'descricao',
        'assinatura_cliente_path',
        'agenda_id',
        'percentual_comissao',
        'tipo_servico',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    // CONFIGURAÇÃO DA BUSCA GLOBAL
    public static $globallySearchableAttributes = ['numero_os', 'descricao'];

    public function getGlobalSearchResultTitle(): string
    {
        return "OS: {$this->numero_os}";
    }

    // Mostra Cliente e Status na busca
    public function getGlobalSearchResultDetails(): array
    {
        return [
            'Cliente' => $this->cadastro->nome ?? 'Desconhecido',
            'Status' => ucfirst($this->status),
        ];
    }

    // Relacionamentos
    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    /**
     * Relacionamento com o cliente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        // Se você unificou, aponta para Cadastro. Se não, aponta para Cliente.
        // Dado que unificamos:
        return $this->belongsTo(Cadastro::class, 'cliente_id');
    }

    /**
     * Relacionamento com o parceiro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parceiro()
    {
        return $this->belongsTo(Cadastro::class, 'parceiro_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Gera número se não existir
            if (empty($model->numero_os)) {
                $model->numero_os = self::gerarNumeroOS();
            }
            
            // Define tipo de serviço padrão se não existir
            if (empty($model->tipo_servico)) {
                $model->tipo_servico = 'servico'; 
            }

            // CORREÇÃO: Define descrição padrão se não existir
            if (empty($model->descricao_servico)) {
                // Tenta usar a descrição genérica ou pega do orçamento
                $model->descricao_servico = $model->descricao ?? 'Serviço conforme orçamento';
            }

            // CORREÇÃO: Define data de abertura como HOJE/AGORA
            if (empty($model->data_abertura)) {
                $model->data_abertura = now();
            }

            // CORREÇÃO: Define o usuário criador
            if (empty($model->criado_por)) {
                $model->criado_por = auth()->id() ?? 1; // Fallback para ID 1 se for job/cli
            }
        });
    }

    /**
     * Gera um número sequencial único para a Ordem de Serviço.
     *
     * @return string
     */
    public static function gerarNumeroOS(): string
    {
        $ano = date('Y');
        // ATENÇÃO: Mudou de 'numero' para 'numero_os'
        $ultimaOS = self::whereYear('created_at', $ano)
                        ->orderBy('id', 'desc')
                        ->first();

        if (!$ultimaOS) {
            return "{$ano}.0001";
        }

        $partes = explode('.', $ultimaOS->numero_os); // <--- Aqui também
        
        if (count($partes) < 2) {
             return "{$ano}.0001";
        }

        $sequencia = intval($partes[1]) + 1;
        return $ano . '.' . str_pad($sequencia, 4, '0', STR_PAD_LEFT);
    }
}
