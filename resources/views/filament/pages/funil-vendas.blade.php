<x-filament-panels::page>
    <div class="responsive-kanban min-h-[calc(100vh-12rem)]">
        @foreach($statuses as $statusKey => $statusData)
            <div
                class="responsive-kanban-column flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 h-full">
                <!-- Header da Coluna -->
                <div
                    class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between sticky top-0 bg-white dark:bg-gray-900 z-10 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        @if(isset($statusData['icon']))
                            <x-filament::icon :icon="$statusData['icon']" class="h-5 w-5 text-gray-500" />
                        @endif
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ $statusData['title'] }}
                        </h3>
                    </div>
                    <span
                        class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium px-2 py-0.5 rounded-full">
                        {{ $orcamentos->where('etapa_funil', $statusKey)->count() }}
                    </span>
                </div>

                <!-- Lista de Cards -->
                <div class="p-2 flex-1 overflow-y-auto space-y-3 bg-gray-50 dark:bg-gray-950/50">
                    @forelse($orcamentos->where('etapa_funil', $statusKey) as $orcamento)
                        <div
                            class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow group relative">

                            <!-- Card Header -->
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-xs font-mono text-gray-400">#{{ $orcamento->numero }}</span>
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1">
                                        {{ $orcamento->cliente->nome ?? 'Cliente não identificado' }}
                                    </h4>
                                </div>
                                <div
                                    class="text-xs font-bold text-primary-600 bg-primary-50 dark:bg-primary-900/20 dark:text-primary-400 px-2 py-1 rounded">
                                    R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3 space-y-1">
                                <div class="flex items-center gap-1">
                                    <x-heroicon-m-calendar class="w-3 h-3" />
                                    {{ $orcamento->created_at->format('d/m/Y') }}
                                </div>
                                @if($orcamento->tipo_servico)
                                    <div class="flex items-center gap-1">
                                        <x-heroicon-m-tag class="w-3 h-3" />
                                        {{ ucfirst($orcamento->tipo_servico) }}
                                    </div>
                                @endif
                            </div>

                            <!-- Actions Footer -->
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <a href="{{ \App\Filament\Resources\OrcamentoResource::getUrl('edit', ['record' => $orcamento]) }}"
                                    class="text-xs text-gray-500 hover:text-primary-600 flex items-center gap-1 hover:underline">
                                    <x-heroicon-m-pencil-square class="w-3 h-3" /> Editar
                                </a>

                                <!-- Dropdown simples para mover -->
                                <x-filament::dropdown>
                                    <x-slot name="trigger">
                                        <button
                                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                            <x-heroicon-m-ellipsis-horizontal class="w-4 h-4" />
                                        </button>
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.header color="gray">Mover para:</x-filament::dropdown.header>
                                        @foreach($statuses as $key => $data)
                                            @if($key !== $statusKey)
                                                <x-filament::dropdown.list.item
                                                    wire:click="updateStatus({{ $orcamento->id }}, '{{ $key }}')"
                                                    icon="{{ $data['icon'] ?? 'heroicon-m-arrow-right' }}">
                                                    {{ $data['title'] }}
                                                </x-filament::dropdown.list.item>
                                            @endif
                                        @endforeach
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-8 px-4 text-gray-400 text-xs border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-lg">
                            Nenhum card nesta etapa
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>