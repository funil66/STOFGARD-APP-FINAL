<x-filament::widget>
    {{-- Importando Fonte Exótica para o banner --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&display=swap');
    </style>

    <div class="flex flex-col gap-8 w-full">

        {{-- HEADER (Faixa Azul com Gradiente Premium) --}}
        <div class="w-full rounded-2xl shadow-2xl overflow-hidden relative text-white px-8 py-8"
            style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);">

            {{-- Padrão de Fundo Sutil --}}
            <div class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;">
            </div>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">

                {{-- 1. SAUDAÇÃO --}}
                <div class="flex-1 text-center md:text-left min-w-[200px]">
                    <div
                        class="inline-flex items-center gap-2 mb-3 bg-white/10 px-4 py-1.5 rounded-full border border-white/20 shadow-sm backdrop-blur-md">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse box-shadow-green"></span>
                        <span class="text-xs font-semibold tracking-wide text-blue-50 uppercase">Sistema Online</span>
                    </div>
                    <h2 class="text-3xl font-bold tracking-tight text-white mb-2 drop-shadow-md">
                        Olá, {{ auth()->user()->name ?? 'Usuário' }}
                    </h2>
                    <p class="text-blue-100/90 text-sm font-medium">
                        Tenha um dia de trabalho produtivo.
                    </p>
                </div>

                {{-- 2. DEUS SEJA LOUVADO (Fonte Cinzel) --}}
                <div class="flex-1 text-center min-w-[300px]">
                    <h1 class="text-3xl md:text-5xl font-black uppercase text-transparent bg-clip-text bg-gradient-to-t from-blue-100 via-white to-blue-50 drop-shadow-[0_4px_10px_rgba(0,0,0,0.3)]"
                        style="font-family: 'Cinzel', serif; letter-spacing: 0.1em; line-height: 1.2;">
                        Deus Seja Louvado
                    </h1>
                    <div
                        class="w-32 h-1 bg-gradient-to-r from-transparent via-white to-transparent mx-auto mt-4 opacity-60 rounded-full">
                    </div>
                </div>

                {{-- 3. CLIMA (Estilo Card Colorido) --}}
                <div class="flex-1 flex flex-col items-center md:items-end justify-center min-w-[200px]">
                    <div class="rounded-xl overflow-hidden shadow-xl border border-white/20 relative group transition-transform duration-300 hover:scale-[1.02]"
                        style="width: 300px; height: 100px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px);">

                        @if(!empty($weatherUrl))
                            <iframe src="{{ $weatherUrl }}" style="width: 100%; height: 100%; border: 0;" scrolling="no">
                            </iframe>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-white/80 gap-2">
                                <x-heroicon-o-sun class="w-8 h-8 text-yellow-300 animate-spin-slow" />
                                <span class="text-xs font-medium tracking-wider">CLIMA NÃO CONFIGURADO</span>
                            </div>
                        @endif

                        {{-- Botão Config --}}
                        <a href="/admin/configuracoes"
                            class="absolute top-2 right-2 p-1.5 rounded-full bg-white/20 hover:bg-white text-white hover:text-blue-600 transition-all shadow-lg"
                            title="Configurar Widget">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRID DE ÍCONES (4x2) --}}
        {{-- Usando GRID INLINE garantido --}}
        <div
            style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; padding: 0 1rem; justify-items: center;">
            @foreach ($shortcuts as $shortcut)
                <div class="flex items-center justify-center w-full">
                    <a href="{{ $shortcut['url'] }}"
                        class="group flex flex-col items-center justify-center transition-all duration-300 hover:-translate-y-2"
                        style="width: fit-content;">

                        {{-- ÍCONE COLORIDO SEM CARD --}}
                        {{-- Removido backgrounds brancos. Apenas o ícone vibrante. --}}
                        <div
                            class="mb-4 transition-transform duration-300 group-hover:scale-110 relative filter drop-shadow-xl p-4">
                            {{-- Componente Dinâmico do Filament para garantir renderização --}}
                            <x-dynamic-component :component="$shortcut['icon']" class="w-16 h-16"
                                style="color: {{ $shortcut['color'] }}; filter: drop-shadow(0 4px 10px {{ $shortcut['color'] }}60);" />
                        </div>

                        {{-- TEXTO --}}
                        <span
                            class="text-base font-bold text-gray-600 dark:text-gray-300 text-center uppercase tracking-wider group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                            style="text-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            {{ $shortcut['label'] }}
                        </span>
                    </a>
                </div>
            @endforeach
        </div>

    </div>
</x-filament::widget>