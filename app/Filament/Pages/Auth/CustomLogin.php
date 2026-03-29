<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class CustomLogin extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        $token = request()->query('token');

        if ($token) {
            $userId = Cache::pull('central_auth_token_' . $token);

            if ($userId) {
                $user = User::find($userId);
                
                if ($user) {
                    Auth::guard('web')->login($user);
                    session()->regenerate();
                    
                    redirect()->intended(filament()->getUrl());
                }
            }
        }
    }
}
