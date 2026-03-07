<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane" color="primary">
                Enviar Ticket de Suporte
            </x-filament::button>
        </div>
    </form>

    {{-- Tickets anteriores --}}
    @if($this->tickets->count() > 0)
        <x-filament::section heading="📋 Seus Tickets Anteriores" collapsible collapsed class="mt-6">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->tickets as $ticket)
                    <div class="py-3 flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                                {{ $ticket->assunto }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </p>
                            @if($ticket->resposta_admin)
                                <div
                                    class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <p class="text-xs font-medium text-green-800 dark:text-green-200">💬 Resposta do Suporte:</p>
                                    <p class="text-xs text-green-700 dark:text-green-300 mt-1">{{ $ticket->resposta_admin }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @php
                                $badgeColor = match ($ticket->status) {
                                    'aberto' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    'em_andamento' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    'resolvido' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                    'fechado' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $badgeColor }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>