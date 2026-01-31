<x-filament-panels::page>
    <div class="grid gap-6">
        <!-- Stats Widgets -->
        <div>
            @livewire(App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroStatsWidget::class)
        </div>

        <!-- Chart Widget -->
        <div>
            @livewire(App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroChartWidget::class)
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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