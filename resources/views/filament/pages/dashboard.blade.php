<x-filament-panels::page>
    {{-- Container Principal sem espaçamento extra --}}
    <div class="-mt-4 md:-mt-8">

        {{-- FAIXA AZUL (Layout Responsivo) --}}
        <div style="background: linear-gradient(90deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%); border-radius: 0 0 20px 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);"
            class="w-full mb-6 md:mb-10 overflow-hidden relative">
            <div class="dashboard-banner relative z-10">

                {{-- Saudação (Mobile: Centralizado, Desktop: Esquerda) --}}
                <div class="dashboard-banner-greeting text-white">
                    <h2 class="text-xl md:text-2xl font-bold mb-1">
                        @php
                            $hour = date('H');
                            $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
                            $name = auth()->user()->name;
                            $firstName = explode(' ', $name)[0];
                        @endphp
                        {{ $greeting }}, {{ $firstName }}!
                    </h2>
                    <p class="text-blue-100 text-xs md:text-sm opacity-90 capitalize">
                        {{ \Carbon\Carbon::now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </p>
                </div>

                {{-- Frase Central (Configurável) --}}
                <div class="dashboard-banner-phrase">
                    <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold tracking-[0.1em] md:tracking-[0.2em] text-white"
                        style="text-shadow: 0 2px 4px rgba(0,0,0,0.2); font-family: ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif;">
                        {{ strtoupper(settings('dashboard_frase', 'Bem-vindo ao Sistema')) }}
                    </h1>
                </div>

                {{-- Widget de Clima (Alpine.js) --}}
                <div class="dashboard-banner-weather" x-data="{
                    temp: '--',
                    icon: '☀️',
                    desc: 'Carregando...',
                    async init() {
                        try {
                            const res = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-21.1767&longitude=-47.8208&current=temperature_2m,weather_code&timezone=America/Sao_Paulo');
                            const data = await res.json();
                            if (data.current) {
                                this.temp = Math.round(data.current.temperature_2m);
                                this.icon = this.getIcon(data.current.weather_code);
                                this.desc = 'Ribeirão Preto';
                            }
                        } catch (e) { console.error(e); }
                    },
                    getIcon(code) {
                        if (code === 0) return '☀️';
                        if (code <= 3) return '⛅';
                        if (code <= 48) return '🌫️';
                        if (code <= 67) return '🌧️';
                        if (code >= 95) return '⛈️';
                        return '🌧️';
                    }
                }">
                    <div
                        class="responsive-weather flex items-center gap-3 bg-white/20 backdrop-blur-md px-4 md:px-5 py-2 md:py-3 rounded-2xl border border-white/10 text-white">
                        <div x-text="icon" class="text-2xl md:text-3xl"></div>
                        <div class="text-right flex-1">
                            <div class="text-xl md:text-2xl font-bold leading-none"><span x-text="temp"></span>°C</div>
                            <div class="text-[9px] md:text-[10px] uppercase tracking-wider opacity-90 mt-1"
                                x-text="desc"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MENU DE CARDS (Grid Responsivo) --}}
        <div class="responsive-grid-modules px-2 sm:px-4 pb-6 md:pb-10">
            @if(isset($modules) && count($modules) > 0)
                @foreach ($modules as $module)
                    <a href="{{ $module['route'] }}"
                        class="responsive-module-card group flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:-translate-y-1 w-full"
                        style="text-decoration: none;">
                        <div class="mb-2 md:mb-3 p-2 rounded-lg transition-colors duration-300 group-hover:bg-opacity-20"
                            style="color: {{ $module['color'] }};">
                            <x-icon :name="$module['icon']" class="w-8 h-8 md:w-10 md:h-10" />
                        </div>
                        <span
                            class="text-sm md:text-base font-semibold text-gray-600 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors text-center px-1">
                            {{ $module['name'] }}
                        </span>
                    </a>
                @endforeach
            @else
                <div class="text-center text-gray-500 w-full py-10 col-span-full">
                    <p>Carregando módulos...</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>