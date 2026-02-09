<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;
use App\Traits\HasAuditTrail;

class OrdemServico extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasArquivos, HasAuditTrail;

    // --- CORREÇÃO DO ERRO DE FK ---
    // Define explicitamente o nome da tabela no banco
    protected $table = 'ordens_servico';

    protected $fillable = [
        'cadastro_id',
        'orcamento_id',
        'numero_os',
        'status',
        'tipo_servico',
        'descricao_servico',
        'data_abertura',
        'data_prevista',
        'data_conclusao',
        'valor_total',
        'desconto',
        'criado_por',
        'dias_garantia',
        'loja_id',
        'vendedor_id',
        'funcionario_id',
        'id_parceiro',
        'origem',
        'extra_attributes',
    ];

    protected $casts = [
        'data_abertura' => 'datetime',
        'data_prevista' => 'datetime',
        'data_conclusao' => 'datetime', // Mantém datetime para precisão
        'valor_total' => 'decimal:2',
        'extra_attributes' => 'array',
    ];

    // --- GARANTIA ---

    // Retorna a data final da garantia (se houver conclusão e dias > 0)
    public function getDataFimGarantiaAttribute()
    {
        if (!$this->data_conclusao || !$this->dias_garantia) {
            return null;
        }
        // Garante que é Carbon
        return \Carbon\Carbon::parse($this->data_conclusao)->addDays($this->dias_garantia);
    }

    // Retorna o status da garantia: 'ativa', 'vencida', 'nenhuma', 'pendente' (se não concluiu)
    public function getStatusGarantiaAttribute(): string
    {
        if (!$this->dias_garantia) {
            return 'nenhuma';
        }

        if (!$this->data_conclusao) {
            return 'pendente'; // Ainda não concluiu o serviço
        }

        $fim = $this->data_fim_garantia;

        if (now()->startOfDay()->lte($fim)) {
            return 'ativa';
        }

        return 'vencida';
    }

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

    // Relacionamento PRINCIPAL com o cliente (Unificado)
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'cadastro_id');
    }

    // Alias para manter compatibilidade, se necessário
    public function cadastro(): BelongsTo
    {
        return $this->cliente();
    }

    /**
     * Relacionamento com o parceiro.
     */
    public function parceiro()
    {
        return $this->belongsTo(Cadastro::class, 'parceiro_id'); // Legacy?
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    public function financeiro(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Financeiro::class, 'ordem_servico_id');
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'loja_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'vendedor_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'funcionario_id');
    }

    // Relacionamento com produtos do estoque utilizados na OS
    public function produtosUtilizados(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Estoque::class, 'ordem_servico_estoque')
            ->withPivot(['quantidade_utilizada', 'unidade', 'observacao'])
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // ALWAYS generate a new numero_os to prevent stale values from forms
            // This ensures the number is fresh even if the form had a pre-generated value
            $model->numero_os = self::gerarNumeroOS();

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

            // CORREÇÃO: Define o usuário criador (obrigatório para auditoria)
            if (empty($model->criado_por)) {
                if (auth()->id()) {
                    $model->criado_por = auth()->id();
                } else {
                    // Em ambiente CLI/Job sem usuário, não deve criar sem responsabilidade
                    throw new \Exception('Não é possível criar Ordem de Serviço sem usuário responsável. Defina criado_por explicitamente.');
                }
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
        // Wrap in transaction for atomicity
        return \DB::transaction(function () {
            $ano = date('Y');

            // Get all OS numbers for this year and find the max sequence
            // We need to parse them because numero_os is a string field
            // Include soft-deleted records to avoid reusing numbers
            $osNumeros = self::withTrashed()
                ->where('numero_os', 'LIKE', "{$ano}.%")
                ->pluck('numero_os')
                ->map(function ($numero) {
                    $partes = explode('.', $numero);
                    return count($partes) >= 2 ? intval($partes[1]) : 0;
                })
                ->filter()
                ->toArray();

            if (empty($osNumeros)) {
                return "{$ano}.0001";
            }

            $maxSequencia = max($osNumeros);
            $novaSequencia = $maxSequencia + 1;

            return $ano . '.' . str_pad($novaSequencia, 4, '0', STR_PAD_LEFT);
        });
    }
}
