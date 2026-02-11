<x-filament-panels::page>
    <x-financeiro-nav />

    <x-filament-widgets::widgets :columns="$this->getHeaderWidgetsColumns()" :data="$this->getWidgetData()"
        :widgets="$this->getHeaderWidgets()" />
</x-filament-panels::page>