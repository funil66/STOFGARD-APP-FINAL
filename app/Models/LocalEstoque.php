<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalEstoque extends Model
{
    protected $table = 'locais_estoque';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function estoques(): HasMany
    {
        return $this->hasMany(Estoque::class, 'local_estoque_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'local_estoque_id');
    }
}
