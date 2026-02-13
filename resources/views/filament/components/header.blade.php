{{-- Barra superior fixa com logo e controles - sempre visível --}}
<div
    class="flex items-center justify-between mb-4 md:mb-6 px-2 sm:px-4 sticky top-0 bg-white dark:bg-gray-900 z-50 py-3 md:py-4 shadow-sm dark:shadow-gray-800/50">
    <div class="flex items-center gap-2">
        <!-- Mobile Menu Button (Hamburger) -->
        <button x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
            class="lg:hidden p-2 -ml-2 mr-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
            type="button" aria-label="Open sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
            </svg>
        </button>

        {{-- Logo com link para dashboard --}}
        <a href="{{ route('filament.admin.pages.dashboard') }}"
            class="flex items-center gap-2 hover:opacity-80 transition-opacity" title="Ir para Dashboard">
            @php
                $logoUrl = settings()->logo();
                $nomeSistema = settings('nome_sistema', 'Sistema');
            @endphp
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $nomeSistema }}" class="h-8 sm:h-10 md:h-12 object-contain">
            @else
                <svg class="h-8 sm:h-10 md:h-12 w-auto" viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="40" font-family="Arial, sans-serif" font-size="32" font-weight="bold"
                        fill="#2563eb">{{ $nomeSistema }}</text>
                </svg>
            @endif
        </a>
    </div>

    {{-- Controles do usuário --}}
    <div class="flex items-center gap-2 md:gap-4">
        @auth
            {{-- Nome do usuário (escondido em mobile) --}}
            <span
                class="hidden md:inline text-sm font-medium text-gray-600 dark:text-gray-300">{{ auth()->user()->name }}</span>

            {{-- Botão de Busca Universal --}}
            <a href="{{ url('/admin/busca-universal') }}"
                class="p-2 md:p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all touch-target"
                title="Busca Universal">
                <svg class="w-5 h-5 md:w-6 md:h-6 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2" fill="none" />
                </svg>
            </a>

            @php
                try {
                    $unreadCount = \App\Services\NotificationService::getUnreadCount(auth()->user());
                } catch (\Throwable $e) {
                    $unreadCount = 0;
                }
            @endphp

            <a href="{{ route('filament.admin.pages.notifications') }}"
                class="relative p-2 md:p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all touch-target"
                title="Notificações">
                <svg class="w-5 h-5 md:w-6 md:h-6 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                </svg>
                @if($unreadCount > 0)
                    <span
                        class="absolute -top-0.5 -right-0.5 md:-top-1 md:-right-1 flex h-4 w-4 md:h-5 md:w-5 items-center justify-center rounded-full text-[10px] md:text-xs font-bold bg-red-500 text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </a>

            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="inline">
                @csrf
                <button type="submit"
                    class="p-2 md:p-2.5 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 transition-all touch-target"
                    title="Sair" onclick="return confirm('Deseja realmente sair do sistema?')">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                    </svg>
                </button>
            </form>
        @endauth

        @guest
            <a href="{{ url('/admin/login') }}"
                class="inline-block px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 touch-target">Entrar</a>
        @endguest
    </div>
</div>