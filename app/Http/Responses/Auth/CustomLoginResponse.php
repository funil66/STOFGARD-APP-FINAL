<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportRedirects\Redirector;

class CustomLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        Log::info("DEBUG: LoginResponse toResponse() called - using direct redirect.");
        // NOTE: Filament::getUrl() causes a deadlock (likely a DB query during URL generation)
        // Redirecting directly to the super-admin panel instead.
        return redirect()->intended('/super-admin');
    }
}
