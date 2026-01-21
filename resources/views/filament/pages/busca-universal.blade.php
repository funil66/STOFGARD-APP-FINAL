<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de Busca --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-magnifying-glass class="w-6 h-6" />
                    <span>Buscar em todo o sistema</span>
                </div>
            </x-slot>
            
            <x-slot name="description">
                Pesquise por ID, nome, CPF, telefone, endereço ou qualquer informação em todos os módulos do sistema.
            </x-slot>
            
            <form wire:submit="buscar">
                {{ $this->form }}
                
                <div class="mt-6 flex gap-3">
                    <x-filament::button type="submit" size="lg">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 mr-2" />
                        Buscar
                    </x-filament::button>
                    
                    <x-filament::button type="button" color="gray" wire:click="limpar">
                        <x-heroicon-o-x-mark class="w-5 h-5 mr-2" />
                        Limpar
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
        
        {{-- Resultados --}}
        @if($totalResultados > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Resultados da Busca</span>
                        <x-filament::badge size="lg">
                            {{ $totalResultados }} {{ $totalResultados === 1 ? 'resultado' : 'resultados' }}
                        </x-filament::badge>
                    </div>
                </x-slot>
                
                <div class="space-y-3">
                    @foreach($resultados as $resultado)
                        <a href="{{ $resultado['url'] }}" 
                           class="block p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start gap-4">
                                {{-- Badge do Tipo --}}
                                <div class="flex-shrink-0 pt-1">
                                    <x-filament::badge :color="$resultado['tipo_color']" size="lg">
                                        {{ $resultado['tipo_label'] }}
                                    </x-filament::badge>
                                </div>
                                
                                {{-- Conteúdo --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Título --}}
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $resultado['titulo'] }}
                                    </h3>
                                    
                                    {{-- Subtítulo --}}
                                    @if($resultado['subtitulo'])
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $resultado['subtitulo'] }}
                                        </p>
                                    @endif
                                    
                                    {{-- Descrição --}}
                                    @if($resultado['descricao'])
                                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2 line-clamp-2">
                                            {{ $resultado['descricao'] }}
                                        </p>
                                    @endif
                                </div>
                                
                                {{-- Data e Ícone --}}
                                <div class="flex-shrink-0 text-right">
                                    @if($resultado['data'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            {{ $resultado['data'] }}
                                        </p>
                                    @endif
                                    <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @elseif($termo || $dataInicio || $dataFim)
            <x-filament::section>
                <div class="text-center py-12">
                    <x-heroicon-o-magnifying-glass class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Nenhum resultado encontrado
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Tente usar outros termos de busca ou remover os filtros de data.
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
                        Digite algo no campo acima para pesquisar em todos os módulos do sistema.
                    </p>
                    
                    {{-- Dicas de Busca --}}
                    <div class="max-w-2xl mx-auto text-left bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="w-5 h-5" />
                            Dicas de busca
                        </h4>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Por ID:</strong> Digite o número (ex: "123" encontra Orçamento #123)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Por nome:</strong> Digite parte do nome (ex: "João Silva")</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Por telefone:</strong> Digite o telefone (com ou sem formatação)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Por CPF/CNPJ:</strong> Digite o documento completo ou parcial</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Por endereço:</strong> Digite rua, bairro ou cidade</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-500">•</span>
                                <span><strong>Use filtros:</strong> Selecione um módulo específico ou período de datas</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
