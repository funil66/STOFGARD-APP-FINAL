<x-filament-panels::page>
    {{-- Widgets do cabeçalho com collapse (AlpineJS) --}}
    <div class="space-y-4">
        {{-- Widget 1: FinanceiroStatsWidget --}}
        <div x-data="{ collapsed: false }" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                 @click="collapsed = !collapsed">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">📋 Estatísticas Financeiras</h3>
                <button type="button" class="flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </button>
            </div>
            <div x-show="!collapsed" x-transition class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroStatsWidget::class)
            </div>
        </div>

        {{-- Widget 2: FinanceiroChartWidget --}}
        <div x-data="{ collapsed: false }" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                 @click="collapsed = !collapsed">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">📈 Receitas vs Despesas (Últimos 6 Meses)</h3>
                <button type="button" class="flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </button>
            </div>
            <div x-show="!collapsed" x-transition class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroChartWidget::class)
            </div>
        </div>

        {{-- Widget 3: FluxoCaixaChart --}}
        <div x-data="{ collapsed: false }" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                 @click="collapsed = !collapsed">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">📉 Fluxo de Caixa (Últimos 6 Meses)</h3>
                <button type="button" class="flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </button>
            </div>
            <div x-show="!collapsed" x-transition class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\FluxoCaixaChart::class)
            </div>
        </div>

        {{-- Widget 4: DespesasCategoriaChart --}}
        <div x-data="{ collapsed: false }" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                 @click="collapsed = !collapsed">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">💰 Despesas por Categoria (Neste Mês)</h3>
                <button type="button" class="flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </button>
            </div>
            <div x-show="!collapsed" x-transition class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\DespesasCategoriaChart::class)
            </div>
        </div>

        {{-- Widget 5: FinanceiroOverview --}}
        <div x-data="{ collapsed: false }" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                 @click="collapsed = !collapsed">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">📊 Overview Financeiro</h3>
                <button type="button" class="flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                    <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </button>
            </div>
            <div x-show="!collapsed" x-transition class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                @livewire(\App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroOverview::class)
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nova Receita</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Registrar entrada</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nova Despesa</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Registrar saída</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Relatórios</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Exportar dados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>