<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleToken extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'google_calendar_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se o token está expirado
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Verifica se o token precisa ser renovado (expira em menos de 5 minutos)
     */
    public function needsRefresh(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->subMinutes(5)->isPast();
    }
}
