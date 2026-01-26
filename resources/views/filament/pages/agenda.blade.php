<x-filament-panels::page>
    <div class='grid grid-cols-1 gap-4'>
        <div class='bg-white p-6 rounded-xl shadow-sm border border-gray-200'>
            <h2 class='text-xl font-bold mb-4'>Agenda de Compromissos</h2>
            {{-- Se você tiver o widget de calendário, descomente abaixo: --}}
            {{-- @livewire(\App\Filament\Widgets\CalendarWidget::class) --}}
            
            <p class='text-gray-500'>O módulo de agenda completa está sendo carregado...</p>
            <div class='mt-4 h-96 bg-gray-50 rounded border border-dashed border-gray-300 flex items-center justify-center'>
                <span class='text-gray-400'>[Área do Calendário FullCalendar]</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
