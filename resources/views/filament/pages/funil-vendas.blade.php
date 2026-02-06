<x-filament-panels::page>
    {{-- FILTROS --}}
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-filament::input.wrapper>
            <x-filament::input
                type="text"
                wire:model.live.debounce.500ms="busca"
                placeholder="🔍 Buscar por nome ou número..."
            />
        </x-filament::input.wrapper>

        <x-filament::input.wrapper>
            <select wire:model.live="filtroVendedor" class="w-full border-gray-300 dark:border-gray-700 rounded-lg">
                <option value="">👤 Todos os Vendedores</option>
                @foreach($vendedores as $id => $nome)
                    <option value="{{ $id }}">{{ $nome }}</option>
                @endforeach
            </select>
        </x-filament::input.wrapper>

        <x-filament::input.wrapper>
            <select wire:model.live="filtroPeriodo" class="w-full border-gray-300 dark:border-gray-700 rounded-lg">
                <option value="todos">📅 Todos os Períodos</option>
                <option value="hoje">Hoje</option>
                <option value="semana">Esta Semana</option>
                <option value="mes">Este Mês</option>
            </select>
        </x-filament::input.wrapper>
    </div>

    {{-- KANBAN BOARD --}}
    <div class="responsive-kanban min-h-[calc(100vh-16rem)]">
        @foreach($statuses as $statusKey => $statusData)
            @php
                $stats = $estatisticas[$statusKey] ?? ['count' => 0, 'total' => 0];
            @endphp
            
            <div class="responsive-kanban-column flex flex-col {{ $statusData['color'] }} rounded-xl shadow-lg border-2 h-full">
                {{-- HEADER DA COLUNA --}}
                <div class="p-4 border-b-2 border-gray-200 dark:border-gray-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm z-10 rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <x-filament::icon 
                            :icon="$statusData['icon']" 
                            class="h-6 w-6 text-gray-700 dark:text-gray-300" 
                        />
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100">
                                {{ $statusData['title'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                R$ {{ number_format($stats['total'], 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <span class="{{ $statusData['badge_color'] }} text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                        {{ $stats['count'] }}
                    </span>
                </div>

                {{-- LISTA DE CARDS --}}
                <div class="p-3 flex-1 overflow-y-auto space-y-3 custom-scrollbar">
                    @forelse($orcamentos->where('etapa_funil', $statusKey) as $orcamento)
                        @php
                            $borderColor = match($statusKey) {
                                'novo' => 'border-gray-500',
                                'contato_realizado' => 'border-blue-500',
                                'agendado' => 'border-yellow-500',
                                'proposta_enviada' => 'border-purple-500',
                                'em_negociacao' => 'border-orange-500',
                                'aprovado' => 'border-green-500',
                                'perdido' => 'border-red-500',
                                default => 'border-gray-500',
                            };
                        @endphp
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-md border-l-4 {{ $borderColor }} hover:shadow-xl transition-all group relative cursor-pointer">
                            
                            {{-- DRAG INDICATOR --}}
                            <div class="absolute left-1 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-heroicon-o-bars-3 class="w-4 h-4 text-gray-400" />
                            </div>

                            {{-- CARD HEADER --}}
                            <div class="flex justify-between items-start mb-3 ml-4">
                                <div class="flex-1">
                                    <span class="text-xs font-mono text-gray-400 dark:text-gray-500">
                                        #{{ $orcamento->numero }}
                                    </span>
                                    <h4 class="font-bold text-gray-900 dark:text-white text-sm leading-tight line-clamp-2 mt-1">
                                        {{ $orcamento->cliente->nome ?? 'Cliente não identificado' }}
                                    </h4>
                                </div>
                                <div class="text-xs font-black text-primary-600 bg-primary-50 dark:bg-primary-900/30 dark:text-primary-400 px-3 py-1.5 rounded-lg shadow-sm">
                                    R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                                </div>
                            </div>

                            {{-- CARD BODY --}}
                            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-2 mb-3 ml-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-m-calendar class="w-4 h-4 flex-shrink-0" />
                                    <span>{{ $orcamento->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-gray-500">{{ $orcamento->created_at->diffForHumans() }}</span>
                                </div>
                                
                                @if($orcamento->tipo_servico)
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-wrench-screwdriver class="w-4 h-4 flex-shrink-0" />
                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $orcamento->tipo_servico)) }}</span>
                                    </div>
                                @endif

                                @if($orcamento->cliente && $orcamento->cliente->telefone)
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-phone class="w-4 h-4 flex-shrink-0 text-green-600" />
                                        <a href="https://wa.me/55{{ preg_replace('/\D/', '', $orcamento->cliente->telefone) }}" 
                                           target="_blank"
                                           class="text-green-600 dark:text-green-400 hover:underline font-medium">
                                            {{ $orcamento->cliente->telefone }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- CARD FOOTER (AÇÕES) --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center ml-4">
                                <a href="{{ \App\Filament\Resources\OrcamentoResource::getUrl('edit', ['record' => $orcamento]) }}"
                                    class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1.5 hover:underline font-medium transition-colors">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" /> 
                                    Editar
                                </a>

                                {{-- DROPDOWN MOVER --}}
                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot name="trigger">
                                        <button class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                                            <x-heroicon-m-ellipsis-horizontal class="w-5 h-5" />
                                        </button>
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.header>
                                            <span class="text-xs font-semibold">Mover para:</span>
                                        </x-filament::dropdown.header>
                                        @foreach($statuses as $key => $data)
                                            @if($key !== $statusKey)
                                                <x-filament::dropdown.list.item
                                                    wire:click="updateStatus({{ $orcamento->id }}, '{{ $key }}')"
                                                    :icon="$data['icon']">
                                                    {{ $data['title'] }}
                                                </x-filament::dropdown.list.item>
                                            @endif
                                        @endforeach
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 px-4 text-gray-400 dark:text-gray-600 text-sm border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                            <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-50" />
                            <p class="font-medium">Nenhum lead nesta etapa</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- CUSTOM STYLES --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.8);
        }
    </style>
</x-filament-panels::page>