<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! auth()->check() && ! $this->exists) {
            return false;
        }

        // Allow seeded main admin or any user with is_admin = true
        $allowed = ($this->email === 'allisson@stofgard.com.br') || ($this->is_admin == true);
        Log::channel('single')->info('canAccessPanel check', [
            'user_id' => $this->id ?? null,
            'email' => $this->email ?? null,
            'is_admin' => $this->is_admin ?? null,
            'panel_id' => (method_exists($panel, 'getId') ? $panel->getId() : null),
            'allowed' => $allowed,
        ]);

        return $allowed;
    }
}
