<x-filament::widget class='col-span-full w-full'>
    <x-filament::section>
        <div class='mb-4 font-bold text-lg text-gray-700 dark:text-gray-200'>Acesso Rápido</div>
        
        {{-- O SEGREDO ESTÁ AQUI: grid-cols-4 --}}
        <div class='grid grid-cols-2 md:grid-cols-4 gap-4'>
            @foreach($this->getShortcuts() as $shortcut)
                <a href='{{ $shortcut['url'] }}' 
                   class='group flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 h-32'>
                    
                    {{-- Círculo Colorido --}}
                    <div class='flex items-center justify-center w-12 h-12 rounded-full text-white shadow-md mb-2 group-hover:scale-110 transition-transform'
                         style='background-color: {{ $shortcut['color'] }};'>
                        <x-icon name='{{ $shortcut['icon'] }}' class='w-6 h-6 text-white' />
                    </div>
                    
                    {{-- Texto --}}
                    <span class='text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wide text-center'>
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
