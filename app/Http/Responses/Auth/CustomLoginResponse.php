<?php

namespace App\Http\Responses\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return match ($panelId) {
            'super-admin' => redirect()->intended('/portal'),
            'cliente' => redirect('/cliente-panel'),
            default => redirect('/admin'),
        };
    }
}
