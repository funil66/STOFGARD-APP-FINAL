<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tarefa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titulo',
        'descricao',
        'status',          // pendente, em_andamento, concluida, cancelada
        'prioridade',      // baixa, media, alta, urgente
        'data_vencimento',
        'data_conclusao',
        'responsavel_id',
        'criado_por',
        'relacionado_type',
        'relacionado_id',
    ];

    protected $casts = [
        'data_vencimento' => 'datetime',
        'data_conclusao' => 'datetime',
    ];

    // Relacionamentos

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function relacionado(): MorphTo
    {
        return $this->morphTo();
    }
}
