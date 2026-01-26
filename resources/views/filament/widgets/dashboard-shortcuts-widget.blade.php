<x-filament::widget class='col-span-full'>
    <x-filament::section>
        <div class='mb-4 flex items-center gap-2'>
            <x-heroicon-o-squares-2x2 class='w-5 h-5 text-gray-500'/>
            <span class='font-bold text-lg text-gray-800 dark:text-gray-200'>Acesso Rápido</span>
        </div>
        
        {{-- GRID RESPONSIVO: 2 colunas no celular, 3 no tablet, 9 (flex) no desktop --}}
        <div class='grid grid-cols-2 md:grid-cols-4 lg:grid-cols-9 gap-4'>
            @foreach($this->getShortcuts() as $shortcut)
                <a href='{{ $shortcut['url'] }}' class='flex flex-col items-center justify-center p-3 h-28 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 group'>
                    
                    {{-- Círculo do Ícone --}}
                    <div class='flex items-center justify-center w-10 h-10 rounded-full text-white shadow-sm mb-2 {{ $shortcut['color'] }} group-hover:scale-110 transition-transform'>
                        @svg($shortcut['icon'], 'w-5 h-5')
                    </div>
                    
                    {{-- Rótulo --}}
                    <span class='text-xs font-bold text-gray-600 dark:text-gray-400 text-center leading-tight group-hover:text-black dark:group-hover:text-white'>
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
