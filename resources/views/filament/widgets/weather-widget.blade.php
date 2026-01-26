<x-filament::widget>
    <div class='relative p-6 rounded-xl bg-blue-600 text-white overflow-hidden shadow-lg flex flex-col md:flex-row items-center justify-between'>
        {{-- Lado Esquerdo: Saudação --}}
        <div class='z-10 text-center md:text-left'>
            <h2 class='text-2xl font-bold'>
                @php
                    $hour = date('H');
                    $saudacao = $hour >= 5 && $hour < 12 ? 'Bom dia' : ($hour >= 12 && $hour < 18 ? 'Boa tarde' : 'Boa noite');
                @endphp
                {{ $saudacao }}, {{ auth()->user()->name ?? 'Allisson' }}!
            </h2>
            <p class='text-blue-100 text-sm mt-1'>
                Hoje é {{ date('l, d \d\e F \d\e Y') }}
            </p>
        </div>
    {{-- Centro: Frase --}}
    <div class='z-10 my-4 md:my-0'>
        <span class='font-black italic text-lg tracking-wider opacity-90'>DEUS SEJA LOUVADO</span>
    </div>
    {{-- Lado Direito: Clima (Estático por enquanto ou Dinâmico) --}}
    <div class='z-10 text-center md:text-right flex items-center gap-3'>
        <div class='text-right'>
            <div class='text-3xl font-bold'>28°C</div>
            <div class='text-xs text-blue-100'>Ribeirão Preto<br>Parcialmente nublado</div>
        </div>
        <x-heroicon-s-sun class='w-10 h-10 text-yellow-300' />
    </div>
    {{-- Background Decoration --}}
    <div class='absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-blue-500 to-transparent opacity-30 transform skew-x-12'></div>
</div>
</x-filament::widget>
