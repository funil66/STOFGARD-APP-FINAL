<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de busca --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-heroicon-o-magnifying-glass-circle class="w-8 h-8 text-primary-600" />
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Busca Global no Sistema
                </h2>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Digite qualquer termo para buscar em todos os módulos do sistema. A busca é realizada em tempo real.
            </p>

            <form wire:submit.prevent="search" class="space-y-4">
                {{ $this->form }}
                
                <x-filament::button 
                    type="submit" 
                    color="primary" 
                    size="lg"
                    icon="heroicon-m-magnifying-glass"
                >
                    Buscar
                </x-filament::button>
            </form>
        </div>

        {{-- Resultados da busca --}}
        @if (!empty($results))
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Resultados encontrados ({{ array_sum(array_column($results, 'count')) }})
                    </h3>
                </div>

                @foreach ($results as $tableName => $result)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-primary-50 dark:bg-primary-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-base font-semibold text-primary-900 dark:text-primary-100">
                                {{ $result['label'] }} ({{ $result['count'] }} resultados)
                            </h4>
                        </div>
                        
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($result['data'] as $item)
                                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach ((array)$item as $key => $value)
                                            @if (!in_array($key, ['password', 'remember_token', 'deleted_at']) && $value !== null)
                                                <div>
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                        {{ str_replace('_', ' ', $key) }}:
                                                    </span>
                                                    <p class="text-sm text-gray-900 dark:text-white font-medium mt-1">
                                                        {{ is_string($value) ? Str::limit($value, 50) : $value }}
                                                    </p>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    
                                    @php
                                        $routes = [
                                            // Clientes UI removed; redirect to unified Cadastros module
                                            'clientes' => '/admin/cadastros',
                                            'ordem_servicos' => '/admin/ordem-servicos',
                                            'agendas' => '/admin/agendas',
                                            'orcamentos' => '/admin/orcamentos',
                                            'financeiros' => '/admin/financeiros',
                                            'estoques' => '/admin/estoques',
                                            'produtos' => '/admin/produtos',
                                            'parceiros' => '/admin/parceiros',
                                            'inventarios' => '/admin/inventarios',
                                        ];
                                        $route = $routes[$tableName] ?? null;
                                    @endphp
                                    
                                    @if ($route && isset($item->id))
                                        <div class="mt-3">
                                            <a href="{{ $route }}/{{ $item->id }}/edit" 
                                               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium inline-flex items-center gap-1">
                                                <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                                Ver detalhes
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($searchTerm && strlen($searchTerm) >= 2)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <x-heroicon-o-magnifying-glass class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Nenhum resultado encontrado
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Tente usar outros termos de busca ou selecione um módulo específico
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
