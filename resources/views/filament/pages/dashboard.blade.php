<x-filament-panels::page>
    <div>
        {{-- Header com gradiente azul Stofgard --}}
        <div class="mb-16 -mx-6 md:-mx-8 lg:-mx-10">
            <div style="background: linear-gradient(120deg, #1e3a8a 0%, #2563eb 45%, #3b82f6 70%, #06b6d4 100%); border-radius: 28px;" class="p-7 md:p-10 shadow-2xl">
                <div class="flex items-center justify-between gap-6">
                    {{-- Mensagem de boas vindas alinhada à esquerda --}}
                    <div style="color: white; flex: 1; min-width: 260px;">
                        <h1 class="text-4xl md:text-5xl font-bold mb-2" style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
                            @php
                                $hour = date('H');
                                $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
                                $date = \Carbon\Carbon::now()->locale('pt_BR');
                            @endphp
                            {{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}!
                        </h1>
                        <p class="text-base md:text-lg" style="color: rgba(255, 255, 255, 0.95);">
                            {{ $date->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        </p>
                    </div>
                    
                    {{-- DEUS SEJA LOUVADO centralizado --}}
                    <div style="flex: 1; text-align: center;">
                        <p class="text-2xl font-black tracking-[0.3em] uppercase" style="color: white; font-family: 'Impact', 'Arial Black', sans-serif; letter-spacing: 0.4em; text-shadow: 3px 3px 6px rgba(0,0,0,0.5), 0 0 10px rgba(255,255,255,0.3);">
                            DEUS SEJA LOUVADO
                        </p>
                    </div>
                    
                    {{-- Widget à direita --}}
                    <div style="flex: 1; display: flex; justify-content: flex-end; min-width: 260px;">
                        {{-- Widget Meteorológico Ribeirão Preto --}}
                        <div class="flex items-center gap-4 px-8 py-6" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); color: white; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18); border-radius: 24px;">
                            <div id="weather-icon" class="text-5xl">
                                ☀️
                            </div>
                            <div style="color: white;">
                                <div class="text-3xl font-bold">
                                    <span id="temperature">--</span>°C
                                </div>
                                <div class="text-xs font-medium mt-1" style="opacity: 0.95;">
                                    Ribeirão Preto
                                </div>
                                <div id="weather-description" class="text-xs mt-1" style="opacity: 0.85;">
                                    Chuva Leve
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid de Módulos --}}
        @php
            try {
                // Safe route helper: checks named route existence and falls back to a URL path
                $s = function ($name, $fallback) {
                try {
                    return \Illuminate\Support\Facades\Route::has($name) ? route($name) : url($fallback);
                } catch (\Exception $e) {
                    return url($fallback);
                }
            };

                $modules = [
                    [
                        'name' => 'Cadastro',
                        'route' => url('/admin/cadastros'),
                        'icon' => 'heroicon-o-users',
                        'color' => '#3b82f6',
                    ],
                    [
                        'name' => 'Ordens de Serviço',
                        'route' => $s('filament.admin.resources.ordem-servicos.index', '/admin/ordem-servicos'),
                        'icon' => 'heroicon-o-clipboard-document-list',
                        'color' => '#ea580c',
                    ],
                    [
                        'name' => 'Agenda',
                        'route' => $s('filament.admin.resources.agendas.index', '/admin/agendas'),
                        'icon' => 'heroicon-o-calendar',
                        'color' => '#8b5cf6',
                    ],
                    [
                        'name' => 'Orçamento',
                        'route' => $s('filament.admin.resources.orcamentos.index', '/admin/orcamentos'),
                        'icon' => 'heroicon-o-currency-dollar',
                        'color' => '#10b981',
                    ],
                    [
                        'name' => 'Financeiro',
                        'route' => $s('filament.admin.resources.transacao-financeiras.index', '/admin/financeiros'),
                        'icon' => 'heroicon-o-banknotes',
                        'color' => '#10b981',
                    ],
                    [
                        'name' => 'Configurações',
                        'route' => $s('filament.admin.pages.configuracoes-gerais', '/admin/configuracoes-gerais'),
                        'icon' => 'heroicon-o-cog-6-tooth',
                        'color' => '#6b7280',
                        'submodules' => [
                            ['name' => 'Gerais', 'icon' => 'heroicon-o-adjustments-horizontal', 'route' => $s('filament.admin.pages.configuracoes-gerais', '/admin/configuracoes-gerais')],
                            ['name' => 'Avançadas', 'icon' => 'heroicon-o-bolt', 'route' => $s('filament.admin.resources.configuracaos.index', '/admin/configuracoes')],
                            ['name' => 'Tabela Preços', 'icon' => 'heroicon-o-currency-dollar', 'route' => $s('filament.admin.resources.tabela-precos.index', '/admin/tabela-precos')],
                            ['name' => 'Categorias', 'icon' => 'heroicon-o-tag', 'route' => $s('filament.admin.resources.categorias.index', '/admin/categorias')],
                            ['name' => 'Garantias', 'icon' => 'heroicon-o-shield-check', 'route' => $s('filament.admin.resources.garantias.index', '/admin/garantias')],
                        ],
                    ],
                ];
            } catch (\Exception $e) {
                // Defensive fallback: if any named route resolution fails, show a minimal set to keep the dashboard functional
                $modules = [
                    ['name' => 'Cadastros', 'route' => url('/admin/cadastros'), 'icon' => 'heroicon-o-users', 'color' => '#3b82f6'],
                ];
            }
        @endphp

        <div style="max-width: 1400px; margin: 40px auto 0; padding: 0 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
                @foreach($modules as $module)
                    <a href="{{ $module['route'] }}" class="group">
                        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 p-8 text-center relative overflow-hidden">
                            {{-- Efeito de cor de fundo sutil --}}
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-5 transition-opacity" style="background-color: {{ $module['color'] }}"></div>
                            
                            {{-- Ícone --}}
                            <div class="relative mb-4">
                                <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center transition-all duration-300 group-hover:scale-110" style="background-color: {{ $module['color'] }}15; color: {{ $module['color'] }}">
                                    @svg($module['icon'], 'w-10 h-10')
                                </div>
                            </div>
                            
                            {{-- Nome --}}
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-gray-700 transition-colors">
                                {{ $module['name'] }}
                            </h3>
                            
                            {{-- Submódulos (se existirem) --}}
                            @if(isset($module['submodules']) && count($module['submodules']) > 0)
                                <div class="mt-4 pt-4 border-t border-gray-100 text-left space-y-1">
                                    @foreach($module['submodules'] as $sub)
                                        <div class="flex items-center gap-2 text-xs text-gray-600 hover:text-blue-600 transition-colors px-2 py-1 rounded hover:bg-blue-50">
                                            @svg($sub['icon'], 'w-3.5 h-3.5 flex-shrink-0')
                                            <span>{{ $sub['name'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        @push('scripts')
        <script>
        // Buscar clima de Ribeirão Preto via Open-Meteo API
        async function fetchWeather() {
            try {
                // Coordenadas de Ribeirão Preto: -21.1767, -47.8208
                const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-21.1767&longitude=-47.8208&current=temperature_2m,weather_code&timezone=America/Sao_Paulo');
                const data = await response.json();
                
                if (data.current) {
                    const temp = Math.round(data.current.temperature_2m);
                    document.getElementById('temperature').textContent = temp;
                    
                    // Atualizar ícone baseado no código do clima
                    const weatherCode = data.current.weather_code;
                    const icon = getWeatherIcon(weatherCode);
                    document.getElementById('weather-icon').textContent = icon;
                }
            } catch (error) {
                console.error('Erro ao buscar clima:', error);
                document.getElementById('temperature').textContent = '--';
            }
        }

        function getWeatherIcon(code) {
            if (code === 0) return '☀️'; // Céu limpo
            if (code <= 3) return '⛅'; // Parcialmente nublado
            if (code <= 48) return '🌫️'; // Nevoeiro
            if (code <= 67) return '🌧️'; // Chuva
            if (code <= 77) return '❄️'; // Neve
            if (code <= 82) return '🌧️'; // Chuva forte
            if (code <= 86) return '🌨️'; // Neve
            return '⛈️'; // Tempestade
        }

        // Buscar clima ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            fetchWeather();
            // Atualizar a cada 30 minutos
            setInterval(fetchWeather, 30 * 60 * 1000);
        });
        </script>
        @endpush
    </div>
</x-filament-panels::page>
