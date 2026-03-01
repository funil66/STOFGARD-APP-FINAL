<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Traits\HasAuditTrail;

class Estoque extends Model implements Auditable
{
    use HasAuditTrail, \OwenIt\Auditing\Auditable;
    protected $table = 'estoques';

    protected $fillable = [
        'item',
        'quantidade',
        'unidade',
        'minimo_alerta',
        'tipo',
        'preco_interno',
        'preco_venda',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'minimo_alerta' => 'decimal:2',
        'preco_interno' => 'decimal:2',
        'preco_venda' => 'decimal:2',
    ];

    // Verifica se está abaixo do mínimo
    public function isAbaixoDoMinimo(): bool
    {
        return $this->quantidade <= $this->minimo_alerta;
    }

    // Calcula quantos galões (20L cada)
    public function getGaloesAttribute(): int
    {
        return (int) floor($this->quantidade / 20);
    }

    // Cor baseada no nível
    public function getCorAttribute(): string
    {
        if ($this->quantidade <= $this->minimo_alerta) {
            return 'danger';
        }
        if ($this->quantidade <= $this->minimo_alerta * 3) {
            return 'warning';
        }
        return 'success';
    }

    // Relacionamento com Ordens de Serviço
    public function ordensServico(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(OrdemServico::class, 'ordem_servico_estoque')
            ->withPivot(['quantidade_utilizada', 'unidade', 'observacao'])
            ->withTimestamps();
    }

    /**
     * Reduz a quantidade do estoque (baixa).
     * 
     * @param float $quantidade
     * @throws \Exception se quantidade insuficiente
     */
    public function darBaixa(float $quantidade): void
    {
        if ($this->quantidade < $quantidade) {
            throw new \Exception(
                "Estoque insuficiente para '{$this->item}'. " .
                "Disponível: {$this->quantidade} {$this->unidade}, " .
                "Solicitado: {$quantidade} {$this->unidade}"
            );
        }

        $this->decrement('quantidade', $quantidade);
    }

    /**
     * Aumenta a quantidade do estoque (estorno).
     * 
     * @param float $quantidade
     */
    public function estornar(float $quantidade): void
    {
        $this->increment('quantidade', $quantidade);
    }
}
