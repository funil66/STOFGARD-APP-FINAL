<x-filament-widgets::widget>
    <x-filament::section>
        <div 
            wire:ignore 
            ax-load
            ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-fullcalendar', 'saade/filament-fullcalendar') }}"
            x-data="fullcalendar({
                ...{{ json_encode($this->getConfig()) }},
                events: (info, successCallback) => $wire.fetchEvents(info).then(successCallback),
                customButtons: {
                    customCreateButton: {
                        text: '➕ Novo',
                        click: function() {
                            window.location.href = '{{ \App\Filament\Resources\AgendaResource::getUrl('create') }}';
                        }
                    }
                }
            })"
            x-on:config-updated.window="refetchEvents()"
            wire:key="{{ $this->getId() }}.calendar"
        >
            <div x-ref="calendar"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
