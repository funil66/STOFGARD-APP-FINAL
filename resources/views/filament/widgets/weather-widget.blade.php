<x-filament::widget class="w-full col-span-full">
    <div class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg text-white p-6">
        {{-- Gradiente de Fundo --}}
        <div class='absolute inset-0 bg-gradient-to-r from-blue-700 to-blue-500'></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 w-full">
            <div class='text-center md:text-left'>
                <h1 class='text-2xl font-bold'>
                    @php
                        $h = date('H');
                        $s = $h < 12 ? 'Bom dia' : ($h < 18 ? 'Boa tarde' : 'Boa noite');
                    @endphp
                    {{ $s }}, {{ auth()->user()->name ?? 'Allisson' }}!
                </h1>
                <p class='mt-1 text-blue-100 opacity-90'>
                    Hoje é {{ date('l, d \d\e F \d\e Y') }}
                </p>
            </div>
        <div class='hidden md:block'>
            <p class='text-lg font-black italic tracking-widest opacity-80'>
                DEUS SEJA LOUVADO
            </p>
        </div>
        <div class='flex items-center gap-3 bg-white/10 px-4 py-2 rounded-lg backdrop-blur-sm'>
            <div class='text-right'>
                <div class='text-3xl font-bold'>28°C</div>
                <div class='text-xs text-blue-50'>Ribeirão Preto</div>
            </div>
            <x-heroicon-s-sun class='w-10 h-10 text-yellow-300' />
        </div>
    </div>
</div>
</x-filament::widget>
