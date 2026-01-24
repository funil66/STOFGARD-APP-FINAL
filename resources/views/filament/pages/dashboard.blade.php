<x-filament-panels::page>
    {{-- Container Principal sem espaçamento extra --}}
    <div class="-mt-8">
        
        {{-- FAIXA AZUL (Estilo Hardcoded para garantir a cor) --}}
        <div style="background: linear-gradient(90deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%); border-radius: 0 0 20px 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="w-full mb-10 overflow-hidden relative">
            <div class="p-8 flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                
                {{-- Saudação (Esquerda) --}}
                <div class="text-white text-center md:text-left">
                    <h2 class="text-2xl font-bold mb-1">
                        @php
                            $hour = date('H');
                            $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
                            $name = auth()->user()->name;
                            $firstName = explode(' ', $name)[0];
                        @endphp
                        {{ $greeting }}, {{ $firstName }}!
                    </h2>
                    <p class="text-blue-100 text-sm opacity-90 capitalize">
                        {{ \Carbon\Carbon::now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </p>
                </div>
            {{-- Frase Central (Centralizada Absoluta em telas grandes) --}}
            <div class="md:absolute md:left-1/2 md:transform md:-translate-x-1/2 text-center">
                <h1 class="text-3xl md:text-4xl font-bold tracking-[0.2em] text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.2); font-family: ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif;">
                    DEUS SEJA LOUVADO
                </h1>
            </div>
            {{-- Widget de Clima (Direita - Alpine.js) --}}
            <div x-data="{
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
            }" class="flex items-center gap-3 bg-white/20 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/10 text-white min-w-[180px]">
                <div x-text="icon" class="text-3xl"></div>
                <div class="text-right flex-1">
                    <div class="text-2xl font-bold leading-none"><span x-text="temp"></span>°C</div>
                    <div class="text-[10px] uppercase tracking-wider opacity-90 mt-1" x-text="desc"></div>
                </div>
            </div>
        </div>
    </div>
    {{-- MENU DE CARDS (Centralizado com Grid) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 justify-items-center gap-8 px-4 pb-10">
        @if(isset($modules) && count($modules) > 0)
            @foreach ($modules as $module)
                <a href="{{ $module['route'] }}"
                   class="group flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:-translate-y-1 w-full"
                   style="max-width: 220px; height: 140px; text-decoration: none;">
                    <div class="mb-3 p-2 rounded-lg transition-colors duration-300 group-hover:bg-opacity-20"
                         style="color: {{ $module['color'] }};">
                        <x-icon :name="$module['icon']" class="w-10 h-10" />
                    </div>
                    <span class="text-base font-semibold text-gray-600 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                        {{ $module['name'] }}
                    </span>
                </a>
            @endforeach
        @else
            <div class="text-center text-gray-500 w-full py-10">
                <p>Carregando módulos...</p>
            </div>
        @endif
    </div>
</div>
</x-filament-panels::page>
