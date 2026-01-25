<x-filament::widget>
    <x-filament::section>
        <div class='mb-4 font-bold text-lg text-gray-700 dark:text-gray-200'>Acesso Rápido</div>
        
        <div class='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-9 gap-4 text-center'>
            @foreach($this->getShortcuts() as $shortcut)
                <a href='{{ $shortcut['url'] }}' class='flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-gray-700 transition group'>
                    <div class='flex items-center justify-center w-10 h-10 mb-2 rounded-full text-white {{ $shortcut['color'] }} shadow-sm group-hover:scale-110 transition-transform'>
                        @svg($shortcut['icon'], 'w-5 h-5')
                    </div>
                    <span class='text-xs font-semibold text-gray-600 dark:text-gray-300 group-hover:text-black dark:group-hover:text-white leading-tight'>
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
