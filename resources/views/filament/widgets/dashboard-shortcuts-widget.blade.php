<x-filament::widget class="w-full col-span-full">
    {{-- Cabeçalho --}}
    <div class="mb-4 flex items-center gap-2 px-2">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Acesso Rápido</h2>
    </div>
    
    {{-- GRID 4x2 ROBUSTO (CSS INLINE PARA GARANTIR) --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; width: 100%;">
        @foreach($this->getShortcuts() as $shortcut)
            <a href="{{ $shortcut['url'] }}" 
               class="group flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 h-40">
                
                {{-- Círculo do Ícone (Maior e mais visível) --}}
                <div class="flex items-center justify-center w-16 h-16 rounded-full text-white shadow-md mb-4 group-hover:scale-110 transition-transform duration-300"
                     style="background-color: {{ $shortcut['color'] }}; box-shadow: 0 4px 6px -1px {{ $shortcut['color'] }}50;">
                    <x-icon name="{{ $shortcut['icon'] }}" class="w-8 h-8 text-white" />
                </div>
                
                {{-- Rótulo --}}
                <span class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide text-center group-hover:text-black dark:group-hover:text-white">
                    {{ $shortcut['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</x-filament::widget>
