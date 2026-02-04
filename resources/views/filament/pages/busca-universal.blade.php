<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de Busca --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-500/10 rounded-lg">
                        <x-heroicon-o-magnifying-glass class="w-6 h-6 text-primary-500" />
                    </div>
                    <div>
                        <span class="text-lg font-bold">Busca Avançada</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                            Pesquise em todos os módulos do sistema
                        </p>
                    </div>
                </div>
            </x-slot>

            {{ $this->form }}

            <x-filament-actions::modals />
        </x-filament::section>

        {{-- Resultados Premium --}}
        @if($totalResultados > 0)
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Resultados da Busca
                </h2>
                <x-filament::badge size="lg" color="primary">
                    {{ $totalResultados }} {{ $totalResultados === 1 ? 'resultado' : 'resultados' }}
                </x-filament::badge>
            </div>

            <div class="responsive-grid-cards">
                @foreach($resultados as $resultado)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            {{-- Header com gradiente --}}
                            <div class="p-4 {{ match ($resultado['tipo']) {
                        'cadastro' => 'bg-gradient-to-r from-indigo-500/10 to-purple-500/10',
                        'orcamento' => 'bg-gradient-to-r from-amber-500/10 to-orange-500/10',
                        'ordem_servico' => 'bg-gradient-to-r from-emerald-500/10 to-green-500/10',
                        'financeiro' => $resultado['tipo_color'] === 'success' ? 'bg-gradient-to-r from-emerald-500/10 to-green-500/10' : 'bg-gradient-to-r from-red-500/10 to-orange-500/10',
                        'agenda' => 'bg-gradient-to-r from-blue-500/10 to-cyan-500/10',
                        'produto' => 'bg-gradient-to-r from-gray-500/10 to-slate-500/10',
                        default => 'bg-gray-100 dark:bg-gray-700',
                    } }}">
                                <div class="flex items-start gap-3">
                                    {{-- Ícone Grande --}}
                                    <div class="text-3xl flex-shrink-0">
                                        {{ $resultado['tipo_icon'] }}
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <x-filament::badge :color="$resultado['tipo_color']" size="sm">
                                                {{ explode(' ', $resultado['tipo_label'], 2)[1] ?? $resultado['tipo_label'] }}
                                            </x-filament::badge>
                                            @if($resultado['status'])
                                                <x-filament::badge :color="$resultado['status_color']" size="sm">
                                                    {{ $resultado['status'] }}
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        <h3 class="font-bold text-gray-900 dark:text-white truncate">
                                            {{ $resultado['titulo'] }}
                                        </h3>
                                    </div>

                                    {{-- Data --}}
                                    @if($resultado['data'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $resultado['data'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Corpo --}}
                            <div class="p-4 pt-2">
                                @if($resultado['subtitulo'])
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ $resultado['subtitulo'] }}
                                    </p>
                                @endif

                                @if($resultado['descricao'])
                                    <p class="text-sm text-gray-500 dark:text-gray-500 line-clamp-2">
                                        {{ $resultado['descricao'] }}
                                    </p>
                                @endif
                            </div>

                            {{-- Ações --}}
                            <div
                                class="px-3 md:px-4 pb-3 md:pb-4 flex flex-col sm:flex-row gap-2 justify-end border-t border-gray-100 dark:border-gray-700 pt-2 md:pt-3">
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
        @elseif($termo || $dataInicio || $dataFim || $statusFiltro)
            <x-filament::section>
                <div class="text-center py-12">
                    <div
                        class="mx-auto w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-magnifying-glass class="w-10 h-10 text-gray-400" />
                    </div>
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
                    <div
                        class="mx-auto w-24 h-24 bg-gradient-to-br from-primary-500/20 to-primary-600/20 rounded-full flex items-center justify-center mb-6">
                        <x-heroicon-o-magnifying-glass class="w-12 h-12 text-primary-500" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        Busca Avançada
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        Digite algo no campo acima para pesquisar em todos os módulos do sistema.
                        A busca acontece automaticamente enquanto você digita.
                    </p>

                    {{-- Cards de Dicas --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 md:gap-4 max-w-2xl mx-auto text-left">
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                            <div class="text-2xl mb-2">👤</div>
                            <h4 class="font-semibold text-indigo-900 dark:text-indigo-100 text-sm">Cadastros</h4>
                            <p class="text-xs text-indigo-700 dark:text-indigo-300">Nome, CPF, telefone</p>
                        </div>
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                            <div class="text-2xl mb-2">📋</div>
                            <h4 class="font-semibold text-amber-900 dark:text-amber-100 text-sm">Orçamentos</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300">Número, cliente, serviço</p>
                        </div>
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <div class="text-2xl mb-2">🛠️</div>
                            <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 text-sm">Ordens de Serviço</h4>
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Número OS, descrição</p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div class="text-2xl mb-2">💰</div>
                            <h4 class="font-semibold text-green-900 dark:text-green-100 text-sm">Financeiro</h4>
                            <p class="text-xs text-green-700 dark:text-green-300">Descrição, categoria</p>
                        </div>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="text-2xl mb-2">📅</div>
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 text-sm">Agenda</h4>
                            <p class="text-xs text-blue-700 dark:text-blue-300">Título, local, data</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <div class="text-2xl mb-2">📦</div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Produtos</h4>
                            <p class="text-xs text-gray-700 dark:text-gray-300">Nome, código, categoria</p>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>