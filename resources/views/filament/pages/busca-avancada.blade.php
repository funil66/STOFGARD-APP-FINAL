<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de Busca --}}
        {{ $this->form }}

        {{-- Resultados --}}
        @if($totalResultados > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Resultados da Busca</span>
                        <x-filament::badge size="lg" color="primary">
                            {{ $totalResultados }} {{ $totalResultados === 1 ? 'resultado' : 'resultados' }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-4">
                    @foreach($resultados as $resultado)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                            {{-- Header --}}
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ $resultado['tipo_icon'] }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <x-filament::badge :color="$resultado['tipo_color']" size="xs">
                                                {{ $resultado['tipo_label'] }}
                                            </x-filament::badge>
                                            <x-filament::badge :color="$resultado['status_color']" size="xs">
                                                {{ $resultado['status'] }}
                                            </x-filament::badge>
                                        </div>
                                        <h3 class="font-bold text-sm md:text-base text-gray-900 dark:text-white truncate mt-1">
                                            {{ $resultado['titulo'] }}
                                        </h3>
                                    </div>
                                    @if($resultado['data'])
                                        <span class="text-xs text-gray-500 whitespace-nowrap">{{ $resultado['data'] }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Corpo --}}
                            <div class="p-4">
                                @if($resultado['subtitulo'])
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ $resultado['subtitulo'] }}
                                    </p>
                                @endif

                                @if($resultado['descricao'])
                                    <p class="text-sm text-gray-500 line-clamp-2">
                                        {{ $resultado['descricao'] }}
                                    </p>
                                @endif
                            </div>

                            {{-- Ações --}}
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2 justify-end">
                                <x-filament::button :href="$resultado['view_url']" tag="a" color="gray" size="sm"
                                    icon="heroicon-o-eye" outlined>
                                    <span class="hidden sm:inline">Visualizar</span>
                                    <span class="sm:hidden">Ver</span>
                                </x-filament::button>

                                <x-filament::button :href="$resultado['edit_url']" tag="a" color="primary" size="sm"
                                    icon="heroicon-o-pencil-square">
                                    Editar
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @elseif($termo || $dataInicio || $dataFim || $statusFiltro)
            <x-filament::section>
                <div class="text-center py-12">
                    <x-heroicon-o-magnifying-glass class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Nenhum resultado encontrado
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Tente usar outros termos de busca ou remover os filtros.
                    </p>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <x-heroicon-o-magnifying-glass class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Comece sua busca
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Preencha os filtros acima e clique no botão <strong>Buscar</strong> no topo da página.
                    </p>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-2xl mx-auto text-left">
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                            <span class="text-2xl">👤</span>
                            <h4 class="font-semibold text-indigo-900 dark:text-indigo-100 text-sm mt-2">Cadastros</h4>
                            <p class="text-xs text-indigo-700 dark:text-indigo-300">Nome, CPF, telefone</p>
                        </div>
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                            <span class="text-2xl">📋</span>
                            <h4 class="font-semibold text-amber-900 dark:text-amber-100 text-sm mt-2">Orçamentos</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300">Número, cliente</p>
                        </div>
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <span class="text-2xl">🛠️</span>
                            <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 text-sm mt-2">Ordens de Serviço
                            </h4>
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Número OS</p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <span class="text-2xl">💰</span>
                            <h4 class="font-semibold text-green-900 dark:text-green-100 text-sm mt-2">Financeiro</h4>
                            <p class="text-xs text-green-700 dark:text-green-300">Descrição</p>
                        </div>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <span class="text-2xl">📅</span>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 text-sm mt-2">Agenda</h4>
                            <p class="text-xs text-blue-700 dark:text-blue-300">Título, local</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <span class="text-2xl">📦</span>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm mt-2">Produtos</h4>
                            <p class="text-xs text-gray-700 dark:text-gray-300">Nome, código</p>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>