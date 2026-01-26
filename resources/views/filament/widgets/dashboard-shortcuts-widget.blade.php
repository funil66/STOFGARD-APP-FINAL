<x-filament::widget class="w-full">
    {{-- Título --}}
    <div class="mb-4 flex items-center gap-2">
        <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-500"/>
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Acesso Rápido</h2>
    </div>
    
    {{-- GRID 3x3 (md:grid-cols-3) --}}
    <div class='grid grid-cols-2 md:grid-cols-4 lg:grid-cols-9 gap-4 w-full'>
        @foreach($this->getShortcuts() as $shortcut)
            <a href='{{ $shortcut['url'] }}' 
               class='flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 h-32 group'>
                <div class='flex items-center justify-center w-12 h-12 rounded-full text-white shadow-md mb-3 group-hover:scale-110 transition-transform' style='background-color: {{ $shortcut['color'] }};'>
                    <x-icon name='{{ $shortcut['icon'] }}' class='w-6 h-6 text-white' />
                </div>
                <span class='text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide'>
                    {{ $shortcut['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</x-filament::widget>
