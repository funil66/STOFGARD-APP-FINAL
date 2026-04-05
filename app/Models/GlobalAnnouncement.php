<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalAnnouncement extends Model
{
    protected $connection = 'pgsql';

    protected $fillable = [
        'title',
        'message',
        'color',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public static function getActive()
    {
        return self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }
}
