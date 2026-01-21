{{-- Barra superior fixa com logo e controles - sempre visível --}}
<div class="flex items-center justify-between mb-6 px-4" style="position: sticky; top: 0; background: white; z-index: 1000; padding-top: 16px; padding-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    {{-- Logo Stofgard com link para dashboard --}}
    <a href="{{ route('filament.admin.pages.dashboard') }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity" title="Ir para Dashboard">
        @if(file_exists(public_path('images/logo-stofgard.png')))
            <img src="{{ asset('images/logo-stofgard.png') }}" alt="Stofgard" style="height: 48px; object-fit: contain;">
        @else
            <svg style="height: 48px; width: 180px;" viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <text x="10" y="40" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="#2563eb">STOFGARD</text>
            </svg>
        @endif
    </a>
    
    {{-- Controles do usuário --}}
    <div class="flex items-center gap-4">
        @auth
            <span class="text-sm font-medium" style="color: #475569;">{{ auth()->user()->name }}</span>
            
            {{-- Botão de Busca Universal --}} 
            <a href="{{ url('/admin/busca-universal') }}" class="p-2 rounded-xl hover:bg-gray-100 transition-all" title="Busca Universal">
                <svg class="w-6 h-6" style="color: #64748b;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2" fill="none"/>
                </svg>
            </a>

            @php
                try {
                    $unreadCount = \App\Services\NotificationService::getUnreadCount(auth()->user());
                } catch (\Throwable $e) {
                    $unreadCount = 0;
                }
            @endphp

            <a href="{{ route('filament.admin.pages.notifications') }}" class="relative p-2 rounded-xl hover:bg-gray-100 transition-all" title="Notificações">
                <svg class="w-6 h-6" style="color: #64748b;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold" style="background: #ef4444; color: white;">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </a>
            
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="inline">
                @csrf
                <button type="submit" class="p-2 rounded-xl hover:bg-red-50 transition-all" title="Sair" onclick="return confirm('Deseja realmente sair do sistema?')">
                    <svg class="w-6 h-6" style="color: #ef4444;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </button>
            </form>
        @endauth

        @guest
            <a href="{{ url('/admin/login') }}" class="inline-block px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100">Entrar</a>
        @endguest
    </div>
</div>
