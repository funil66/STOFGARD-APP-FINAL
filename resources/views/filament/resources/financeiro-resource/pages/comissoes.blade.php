<x-filament-panels::page>
    {{-- Widgets de Estatísticas --}}
    @if (count($this->getHeaderWidgets()))
        <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()" />
    @endif

    {{-- Tabela Principal --}}
    {{ $this->table }}
</x-filament-panels::page>