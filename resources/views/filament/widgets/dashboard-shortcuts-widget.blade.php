<x-filament::widget class="w-full col-span-full">
    {{-- Cabeçalho --}}
    <div class="mb-4 flex items-center gap-2 px-1">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Acesso Rápido</h2>
    </div>
    
    {{-- GRID BLINDADO: Usando style direto para garantir 4 colunas --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; width: 100%;">
        @foreach($this->getShortcuts() as $shortcut)
            <a href="{{ $shortcut['url'] }}" 
               class="group flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 h-32 relative overflow-hidden">
                
                {{-- Barra Lateral Colorida (Design Premium) --}}
                <div class="absolute left-0 top-0 bottom-0 w-1" style="background-color: {{ $shortcut['color'] }}"></div>
                
                {{-- Ícone --}}
                <div class="flex items-center justify-center w-10 h-10 rounded-full mb-3 group-hover:scale-110 transition-transform" style="background-color: {{ $shortcut['color'] }}20; color: {{ $shortcut['color'] }};">
                    <x-icon name="{{ $shortcut['icon'] }}" class="w-6 h-6" style="color: {{ $shortcut['color'] }}" />
                </div>
                
                {{-- Rótulo --}}
                <span class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide text-center">
                    {{ $shortcut['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</x-filament::widget>
