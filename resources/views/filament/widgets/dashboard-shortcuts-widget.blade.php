<x-filament::widget class='col-span-full w-full'>
    <x-filament::section>
        {{-- Cabeçalho --}}
        <div class='flex items-center gap-2 mb-4 px-1'>
            <x-heroicon-o-squares-2x2 class='w-5 h-5 text-gray-500'/>
            <h2 class='text-lg font-bold text-gray-800 dark:text-gray-100'>Acesso Rápido</h2>
        </div>
        
        {{-- GRID: Mobile=2, Tablet=4, Desktop=9 --}}
        <div class='grid grid-cols-2 md:grid-cols-4 lg:grid-cols-9 gap-3'>
            @foreach($this->getShortcuts() as $shortcut)
                <a href='{{ $shortcut['url'] }}' 
                   class='group flex flex-col items-center justify-center p-3 h-28 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300'>
                    
                    {{-- Círculo com COR FORÇADA via Style --}}
                    <div class='flex items-center justify-center w-12 h-12 rounded-full text-white shadow-md mb-2 group-hover:scale-110 transition-transform duration-300'
                         style='background-color: {{ $shortcut['color'] }};'>
                        
                        {{-- Ícone do Filament --}}
                        <x-icon name='{{ $shortcut['icon'] }}' class='w-6 h-6 text-white' />
                    </div>
                    
                    {{-- Texto --}}
                    <span class='text-xs font-bold text-gray-600 dark:text-gray-400 text-center leading-tight group-hover:text-gray-900 dark:group-hover:text-white uppercase tracking-wide'>
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
