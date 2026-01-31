<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-beaker class="w-5 h-5 text-primary-500" />
                Estoque de Produtos
            </div>
        </x-slot>

        <div class="grid gap-6 md:grid-cols-2">
            @forelse ($produtos as $produto)
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 
                        @if($produto['cor'] === 'danger') border-red-500 bg-red-50 dark:bg-red-900/20
                        @elseif($produto['cor'] === 'warning') border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20
                        @else border-green-500 bg-green-50 dark:bg-green-900/20
                        @endif
                    ">
                    {{-- Header do Produto --}}
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                            {{ $produto['nome'] }}
                        </h3>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                                @if($produto['cor'] === 'danger') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                                @elseif($produto['cor'] === 'warning') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300
                                @else bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                                @endif
                            ">
                            {{ $produto['estoque'] }}L
                        </span>
                    </div>

                    {{-- Galões Visuais --}}
                    <div class="flex flex-wrap gap-2 mb-3">
                        @for ($i = 0; $i < min($produto['galoes'], 10); $i++)
                            <div class="flex flex-col items-center" title="20L">
                                <svg class="w-10 h-10 
                                            @if($produto['galoes'] <= 1) text-red-500 animate-pulse
                                            @elseif($produto['galoes'] <= 3) text-yellow-500
                                            @else text-green-500
                                            @endif
                                        " fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M6 3a1 1 0 0 0-1 1v1H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V4a1 1 0 0 0-1-1H6zm1 2h10v1H7V5zm-3 4h16v10H4V9z" />
                                    <rect x="6" y="11" width="12" height="@if($i < $produto['galoes']) 6 @else 0 @endif" rx="1"
                                        class="@if($produto['galoes'] <= 1) fill-red-400 @elseif($produto['galoes'] <= 3) fill-yellow-400 @else fill-green-400 @endif" />
                                </svg>
                            </div>
                        @endfor

                        @if($produto['galoes'] > 10)
                            <span class="flex items-center text-sm text-gray-500">
                                +{{ $produto['galoes'] - 10 }} galões
                            </span>
                        @endif

                        @if($produto['resto'] > 0)
                            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                +{{ $produto['resto'] }}L
                            </div>
                        @endif
                    </div>

                    {{-- Aviso de Escassez --}}
                    @if($produto['estoque'] <= 20)
                        <div
                            class="flex items-center gap-2 p-2 text-sm font-medium text-red-700 bg-red-100 rounded-lg dark:bg-red-900/50 dark:text-red-300 animate-pulse">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                            ⚠️ ESTOQUE CRÍTICO! Reabastecer urgentemente!
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-2 py-8 text-center text-gray-500">
                    <x-heroicon-o-beaker class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>Nenhum produto cadastrado.</p>
                    <p class="text-sm">Cadastre "Impermeabilizante" e "Frotador" para ver o estoque.</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>