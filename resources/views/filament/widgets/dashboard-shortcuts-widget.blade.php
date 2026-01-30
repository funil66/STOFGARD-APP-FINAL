<x-filament::widget>
    <x-filament::section>
        {{-- Cabeçalho com Saudação e Clima --}}
        <div class="flex flex-col md:flex-row gap-6 mb-6 items-start justify-between">
            <div class="flex-1">
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Painel de Controle
                </h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Bem-vindo ao Stofgard System. Selecione um módulo abaixo.
                </p>
            </div>
            
            {{-- Widget de Clima (Carregado do Banco) --}}
            @if($weatherUrl)
                <div class="rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-800">
                    {{-- Se for wttr.in, usamos imagem para ser mais rápido, ou iframe se preferir interativo --}}
                    @if(Str::contains($weatherUrl, 'wttr.in'))
                        <img src="{{ $weatherUrl }}" alt="Clima" class="h-16 object-contain bg-white dark:bg-gray-900">
                    @else
                        <iframe src="{{ $weatherUrl }}" class="w-full h-16 md:w-64 border-0" scrolling="no"></iframe>
                    @endif
                </div>
            @endif
        </div>

        {{-- Grid de Atalhos (8 Módulos) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($shortcuts as $shortcut)
                <a href="{{ $shortcut['url'] }}" 
                   class="group flex flex-col items-center justify-center p-4 rounded-xl border border-gray-200 dark:border-gray-800 hover:border-primary-500 dark:hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all duration-200">
                    
                    <div class="mb-3 p-3 rounded-full bg-opacity-10 transition-colors group-hover:bg-opacity-20"
                         style="background-color: {{ $shortcut['color'] }}20;">
                        <x-icon :name="$shortcut['icon']" class="w-8 h-8" style="color: {{ $shortcut['color'] }};" />
                    </div>
                    
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        {{ $shortcut['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament::widget>
