<x-filament-panels::page>
    {{-- Tabela de Análise por Categoria --}}
    <div class="space-y-6">
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    🏷️ Análise de receitas e despesas agrupadas por categoria. Use os filtros para comparar períodos.
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>