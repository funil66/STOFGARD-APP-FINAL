<x-filament::widget class='col-span-full'>
    <div class='relative p-6 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg overflow-hidden'>
        <div class='relative z-10 flex flex-col md:flex-row items-center justify-between gap-4'>
            
            {{-- Saudação --}}
            <div class='text-center md:text-left'>
                <h2 class='text-3xl font-bold tracking-tight'>
                    @php
                        $h = date('H');
                        $saudacao = $h < 12 ? 'Bom dia' : ($h < 18 ? 'Boa tarde' : 'Boa noite');
                    @endphp
                    {{ $saudacao }}, {{ auth()->user()->name ?? 'Allisson' }}!
                </h2>
                <p class='text-blue-100 mt-1 font-medium opacity-90'>
                    Hoje é {{ date('l, d \d\e F \d\e Y') }}
                </p>
            </div>
        {{-- Frase Central --}}
        <div class='hidden md:block'>
            <span class='text-lg font-black italic tracking-widest opacity-80'>
                DEUS SEJA LOUVADO
            </span>
        </div>
        {{-- Clima --}}
        <div class='flex items-center gap-4 bg-white/10 px-4 py-2 rounded-lg backdrop-blur-sm'>
            <div class='text-right'>
                <div class='text-2xl font-bold'>23°C</div>
                <div class='text-xs text-blue-50 leading-tight'>Ribeirão Preto<br>Parcialmente nublado</div>
            </div>
            <x-heroicon-s-sun class='w-10 h-10 text-yellow-300' />
        </div>
    </div>
    {{-- Elemento Decorativo de Fundo --}}
    <div class='absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full blur-3xl'></div>
</div>
</x-filament::widget>
