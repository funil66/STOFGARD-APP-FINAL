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
            // Recupera o email do usuário do cache central (usando email para evitar divergência de IDs entre Central DB e Tenant DB)
            $userEmail = Cache::pull('central_auth_token_' . $token);

            if ($userEmail) {
                // Busca o usuário na base do Tenant usando o email, em vez do ID
                $user = User::where('email', $userEmail)->first();
                
                if ($user) {
                    Auth::guard('web')->login($user);
                    session()->regenerate();
                    
                    $this->redirect(filament()->getUrl());
                    return;
                }
            }
        }
    }
}
