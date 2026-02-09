<x-filament-panels::page>
    {{-- Stats (Header Widgets) are rendered automatically above --}}

    {{-- Collapsible Charts Section --}}
    <div x-data="{ expanded: false }"
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 mb-6">
        <button @click="expanded = !expanded"
            class="w-full flex items-center justify-between p-4 px-6 text-left font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50 transition first:rounded-xl">
            <span class="flex items-center gap-3 text-lg font-semibold text-primary-600 dark:text-primary-400">
                <x-heroicon-o-chart-pie class="w-6 h-6" />
                Análise Gráfica & Fluxo de Caixa
            </span>
            <x-heroicon-m-chevron-down class="w-5 h-5 text-gray-500 transition-transform duration-200"
                ::class="{ 'rotate-180': expanded }" />
        </button>

        <div x-show="expanded" x-collapse style="display: none;"
            class="border-t border-gray-200 dark:border-gray-800 p-6 bg-gray-50/50 dark:bg-gray-900/50 rounded-b-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @livewire(\App\Filament\Widgets\FluxoCaixaChart::class)
                @livewire(\App\Filament\Widgets\DespesasPorCategoriaChart::class)
            </div>
        </div>
    </div>

    {{-- Recent Transactions Widget --}}
    <div class="mt-8">
        @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\RecentTransactionsWidget::class)
    </div>
</x-filament-panels::page>