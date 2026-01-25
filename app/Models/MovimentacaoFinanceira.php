<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoFinanceira extends Model
{
    protected $table = 'movimentacoes_financeiras';
    protected $guarded = [];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }
}
