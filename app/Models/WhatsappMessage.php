<?php

namespace App\Models;
use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'cadastro_id',
        'remote_message_id',
        'remote_jid',
        'body',
        'type',
        'direction',
        'status',
    ];

    /**
     * O cliente (cadastro) dono desta mensagem.
     */
    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }
}
