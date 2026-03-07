<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cupom extends Model
{
    protected $table = 'cupons';

    protected $fillable = [
        'cliente_indicador_id',
        'codigo',
        'desconto_percentual',
        'usado',
        'data_expiracao',
    ];

    protected $casts = [
        'desconto_percentual' => 'decimal:2',
        'usado' => 'boolean',
        'data_expiracao' => 'datetime',
    ];

    public function clienteIndicador(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'cliente_indicador_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = strtoupper(Str::random(8));
            }
        });
    }
}
