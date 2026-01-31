<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $table = 'estoques';

    protected $fillable = [
        'item',
        'quantidade',
        'unidade',
        'minimo_alerta',
        'tipo',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'minimo_alerta' => 'decimal:2',
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
}
