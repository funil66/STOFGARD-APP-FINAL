<x-filament-panels::page>
    {{-- Widget de Calendário --}}
    <div class="mb-6">
        @livewire(\App\Filament\Widgets\AgendaCalendarWidget::class)
    </div>

    {{-- Tabela de Agendamentos --}}
    <div class="filament-tables-container">
        <div class="filament-tables-table-container">
            <table class="filament-tables-table w-full text-left">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="filament-tables-header-cell px-4 py-2 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Data/Hora</span>
                        </th>
                        <th class="filament-tables-header-cell px-4 py-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Título</span>
                        </th>
                        <th class="filament-tables-header-cell px-4 py-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Cliente</span>
                        </th>
                        <th class="filament-tables-header-cell px-4 py-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tipo</span>
                        </th>
                        <th class="filament-tables-header-cell px-4 py-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status</span>
                        </th>
                        <th class="filament-tables-header-cell px-4 py-2 text-right">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ações</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @foreach(\App\Models\Agenda::with('cliente')
                        ->whereDate('data_hora_inicio', '>=', now())
                        ->orderBy('data_hora_inicio')
                        ->limit(20)
                        ->get() as $agenda)
                        <tr class="filament-tables-row hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <td class="filament-tables-cell px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $agenda->data_hora_inicio?->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </td>
                            <td class="filament-tables-cell px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $agenda->titulo }}
                                    </span>
                                    @if($agenda->local)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <x-heroicon-o-map-pin class="w-3 h-3" />
                                            {{ Str::limit($agenda->local, 40) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="filament-tables-cell px-4 py-3">
                                @if($agenda->cliente)
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $agenda->cliente->nome }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="filament-tables-cell px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-md
                                    @switch($agenda->tipo)
                                        @case('servico') bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 @break
                                        @case('visita') bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 @break
                                        @case('reuniao') bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 @break
                                        @default bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400
                                    @endswitch">
                                    @switch($agenda->tipo)
                                        @case('servico') 🧼 Serviço @break
                                        @case('visita') 👁️ Visita @break
                                        @case('reuniao') 🤝 Reunião @break
                                        @default 📌 Outro
                                    @endswitch
                                </span>
                            </td>
                            <td class="filament-tables-cell px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-md
                                    @switch($agenda->status)
                                        @case('agendado') bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 @break
                                        @case('em_andamento') bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 @break
                                        @case('concluido') bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 @break
                                        @case('cancelado') bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 @break
                                        @default bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400
                                    @endswitch">
                                    {{ ucfirst(str_replace('_', ' ', $agenda->status)) }}
                                </span>
                            </td>
                            <td class="filament-tables-cell px-4 py-3">
                                <div class="flex items-center gap-1 justify-end">
                                    <a href="{{ route('filament.admin.resources.agendas.view', $agenda) }}" 
                                       class="filament-icon-button filament-icon-button-size-sm text-gray-500 hover:text-primary-500"
                                       title="Visualizar">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </a>
                                    <a href="{{ route('filament.admin.resources.agendas.edit', $agenda) }}" 
                                       class="filament-icon-button filament-icon-button-size-sm text-gray-500 hover:text-primary-500"
                                       title="Editar">
                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="filament-tables-pagination-container p-2 border-t border-gray-200 dark:border-white/10">
            <div class="text-center">
                <a href="{{ route('filament.admin.resources.agendas.list') }}" 
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                    Ver todos os agendamentos →
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
