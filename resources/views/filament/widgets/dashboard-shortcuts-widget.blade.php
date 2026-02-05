<x-filament::widget>
    {{-- Importando Fonte Exótica para o banner --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&display=swap');
        
        /* Grid customizável */
        .dashboard-shortcuts-grid {
            display: grid;
            grid-template-columns: repeat({{ $gridColunasMobile ?? 2 }}, minmax(0, 1fr));
            gap: {{ $gridGap ?? '1.5rem' }};
            padding: 0 0.5rem;
        }
        
        @media (min-width: 640px) {
            .dashboard-shortcuts-grid {
                grid-template-columns: repeat({{ $gridColunasDesktop ?? 4 }}, minmax(0, 1fr));
                padding: 0 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .dashboard-shortcuts-grid {
                gap: {{ $gridGap ?? '2rem' }};
            }
        }
        
        /* Banner layout - Esquerda | Centro | Direita */
        .dashboard-banner-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .dashboard-banner-container {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 2rem;
            }
            
            .banner-greeting {
                flex: 0 0 auto;
                max-width: 300px;
                text-align: left;
            }
            
            .banner-phrase {
                flex: 1 1 auto;
                text-align: center;
                padding: 0 1rem;
            }
            
            .banner-weather {
                flex: 0 0 auto;
                text-align: right;
            }
        }
    </style>

    <div class="flex flex-col gap-4 md:gap-8 w-full">

        {{-- HEADER (Faixa Azul com Gradiente Premium) --}}
        <div class="w-full rounded-xl md:rounded-2xl shadow-xl md:shadow-2xl relative text-white px-4 py-4 md:px-8 md:py-8"
            style="background: linear-gradient(135deg, {{ $bannerColorStart ?? '#1e3a8a' }} 0%, {{ $bannerColorEnd ?? '#3b82f6' }} 100%); overflow: visible;">

            {{-- Padrão de Fundo Sutil --}}
            <div class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;">
            </div>

            <div class="relative z-10 dashboard-banner-container">

                {{-- 1. SAUDAÇÃO (Alinhado à Esquerda) --}}
                <div class="banner-greeting">
                    <div
                        class="inline-flex items-center gap-2 mb-2 md:mb-3 bg-white/10 px-3 md:px-4 py-1 md:py-1.5 rounded-full border border-white/20 shadow-sm backdrop-blur-md">
                        <span
                            class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-emerald-400 animate-pulse box-shadow-green"></span>
                        <span class="text-[10px] md:text-xs font-semibold tracking-wide text-blue-50 uppercase">Sistema
                            Online</span>
                    </div>
                    <h2
                        class="text-xl md:text-2xl lg:text-3xl font-bold tracking-tight text-white mb-1 md:mb-2 drop-shadow-md">
                        Olá, {{ auth()->user()->name ?? 'Usuário' }}
                    </h2>
                    <p class="text-blue-100/90 text-xs md:text-sm font-medium">
                        {{ $saudacaoTexto ?? 'Tenha um dia de trabalho produtivo.' }}
                    </p>
                </div>

                {{-- 2. FRASE CENTRAL (Centralizado) --}}
                <div class="banner-phrase">
                    <h1 class="text-lg sm:text-xl md:text-3xl lg:text-5xl font-black uppercase text-transparent bg-clip-text bg-gradient-to-t from-blue-100 via-white to-blue-50 drop-shadow-[0_4px_10px_rgba(0,0,0,0.3)]"
                        style="font-family: 'Cinzel', serif; letter-spacing: 0.05em; line-height: 1.2;">
                        {{ $fraseMotivacional ?? 'BORA TRABALHAR!' }}
                    </h1>
                    <div
                        class="w-16 md:w-32 h-0.5 md:h-1 bg-gradient-to-r from-transparent via-white to-transparent mx-auto mt-2 md:mt-4 opacity-60 rounded-full">
                    </div>
                </div>

                {{-- 3. CLIMA (Alinhado à Direita) --}}
                <div class="banner-weather" wire:ignore>
                    <div id="weather-widget" 
                         data-city="{{ $weatherCity ?? 'São Paulo' }}"
                         class="rounded-xl shadow-lg md:shadow-xl border border-white/20 relative group transition-transform duration-300 hover:scale-[1.02]"
                         style="width: 100%; max-width: 280px; min-height: 80px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); z-index: 50; overflow: visible;">

                        {{-- Skeleton Loading (Estado Inicial) --}}
                        <div id="weather-loading" class="flex flex-col items-center justify-center h-20 text-white/80 gap-2">
                            <div class="flex gap-1">
                                <div class="w-2 h-2 bg-white/60 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                                <div class="w-2 h-2 bg-white/60 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                                <div class="w-2 h-2 bg-white/60 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                            </div>
                            <span class="text-xs font-medium tracking-wider">Carregando clima...</span>
                        </div>

                        {{-- Container de Dados (Oculto até carregar) --}}
                        <div id="weather-content" class="hidden h-20 p-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <img id="weather-icon" src="" alt="" class="w-12 h-12" />
                                <div>
                                    <div class="text-2xl font-bold text-white">
                                        <span id="weather-temp">--</span>°C
                                    </div>
                                    <div class="text-xs text-blue-100" id="weather-desc">--</div>
                                </div>
                            </div>
                            <div class="text-right text-xs text-blue-100">
                                <div id="weather-city" class="font-semibold">--</div>
                                <div>Sensação: <span id="weather-feels">--</span>°C</div>
                            </div>
                        </div>

                        {{-- Mensagem de Erro (Oculto por padrão) --}}
                        <div id="weather-error" class="hidden flex flex-col items-center justify-center h-20 text-white/80 gap-1">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-300" />
                            <span class="text-xs font-medium text-center px-2">Não foi possível carregar o clima</span>
                        </div>

                        {{-- Botão Config --}}
                        <a href="/admin/configuracoes"
                            class="absolute top-2 right-2 p-1.5 rounded-full bg-white/20 hover:bg-white text-white hover:text-blue-600 transition-all shadow-lg z-[60]"
                            title="Configurar Widget">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRID DE ÍCONES (4x2 Responsivo) --}}
        <div class="dashboard-shortcuts-grid">
            @foreach ($shortcuts as $shortcut)
                <div class="flex items-center justify-center w-full">
                    <a href="{{ $shortcut['url'] }}"
                        class="group flex flex-col items-center justify-center transition-all duration-300 hover:-translate-y-2"
                        style="width: fit-content;">

                        {{-- ÍCONE COLORIDO SEM CARD --}}
                        <div
                            class="mb-2 md:mb-4 transition-transform duration-300 group-hover:scale-110 relative filter drop-shadow-xl p-2 md:p-4">
                            <x-dynamic-component :component="$shortcut['icon']"
                                class="w-10 h-10 md:w-14 lg:w-16 md:h-14 lg:h-16"
                                style="color: {{ $shortcut['color'] }}; filter: drop-shadow(0 4px 10px {{ $shortcut['color'] }}60);" />
                        </div>

                        {{-- TEXTO --}}
                        <span
                            class="text-xs md:text-sm lg:text-base font-bold text-gray-600 dark:text-gray-300 text-center uppercase tracking-wide md:tracking-wider group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                            style="text-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            {{ $shortcut['label'] }}
                        </span>
                    </a>
                </div>
            @endforeach
        </div>

    </div>

    @assets
    <script>
        window.weatherWidgetInitialized = false;
        
        function initWeatherWidget() {
            if (window.weatherWidgetInitialized) {
                return;
            }
            
            const widget = document.getElementById('weather-widget');
            if (!widget) {
                return;
            }
            
            const loading = document.getElementById('weather-loading');
            const content = document.getElementById('weather-content');
            const error = document.getElementById('weather-error');
            const city = widget.getAttribute('data-city') || 'São Paulo';
            
            fetch(`/api/widget/weather?city=${encodeURIComponent(city)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(result => {
                if (result.success && result.data) {
                    document.getElementById('weather-icon').src = result.data.icon_url;
                    document.getElementById('weather-temp').textContent = result.data.temperature;
                    document.getElementById('weather-desc').textContent = result.data.description;
                    document.getElementById('weather-city').textContent = result.data.city;
                    document.getElementById('weather-feels').textContent = result.data.feels_like;
                    
                    loading.classList.add('hidden');
                    content.classList.remove('hidden');
                    window.weatherWidgetInitialized = true;
                }
            })
            .catch(err => {
                console.error('[Weather] ❌ Erro:', err);
                loading.classList.add('hidden');
                error.classList.remove('hidden');
            });
        }
        
        // Executa após carregamento
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initWeatherWidget);
        } else {
            setTimeout(initWeatherWidget, 500);
        }
    </script>
    @endassets
</x-filament::widget>