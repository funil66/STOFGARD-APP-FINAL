<x-filament-panels::page>
    {{-- Force re-render of widgets when filters change by using a key if needed, or rely on Livewire --}}
    <x-filament-widgets::widgets :columns="1" :data="$this->getWidgetData()"
        :widgets="$this->getVisibleHeaderWidgets()" />
</x-filament-panels::page>