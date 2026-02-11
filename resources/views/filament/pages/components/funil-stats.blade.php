{{-- resources/views/filament/pages/components/funil-stats.blade.php --}}
<div class="p-6 space-y-6">
    {{-- RESUMO GERAL --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div
            class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl shadow-md border-2 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Total de Leads</span>
                <x-heroicon-o-users class="w-6 h-6 text-blue-600" />
            </div>
            <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                {{ $orcamentos->count() }}
            </p>
        </div>

        <div
            class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl shadow-md border-2 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-green-800 dark:text-green-300">Valor Total</span>
                <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600" />
            </div>
            <p class="text-3xl font-bold text-green-900 dark:text-green-100">
                R$ {{ number_format($orcamentos->sum(fn($o) => $o->valor_efetivo), 2, ',', '.') }}
            </p>
        </div>

        <div
            class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-6 rounded-xl shadow-md border-2 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Taxa de Conversão</span>
                <x-heroicon-o-chart-bar class="w-6 h-6 text-yellow-600" />
            </div>
            <p class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">
                {{ $orcamentos->count() > 0 ? number_format(($orcamentos->where('etapa_funil', 'aprovado')->count() / $orcamentos->count()) * 100, 1) : 0 }}%
            </p>
        </div>

        <div
            class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-xl shadow-md border-2 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-purple-800 dark:text-purple-300">Ticket Médio</span>
                <x-heroicon-o-banknotes class="w-6 h-6 text-purple-600" />
            </div>
            <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                R$
                {{ $orcamentos->count() > 0 ? number_format($orcamentos->avg(fn($o) => $o->valor_efetivo), 2, ',', '.') : '0,00' }}
            </p>
        </div>
    </div>

    {{-- DISTRIBUIÇÃO POR ETAPA --}}
    <div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Distribuição por Etapa</h3>
        <div class="space-y-3">
            @foreach($estatisticas as $key => $stats)
                @php
                    $percentage = $orcamentos->count() > 0 ? ($stats['count'] / $orcamentos->count()) * 100 : 0;
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $statuses[$key]['title'] }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $stats['count'] }} ({{ number_format($percentage, 1) }}%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="{{ $statuses[$key]['badge_color'] }} h-2.5 rounded-full transition-all duration-500"
                            style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- LEADS MAIS VALIOSOS --}}
    <div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🏆 Top 5 Leads Mais Valiosos</h3>
        <div class="space-y-2">
            @foreach($orcamentos->sortByDesc(fn($o) => $o->valor_efetivo)->take(5) as $orc)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $orc->cliente->nome ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $orc->numero }} • {{ $statuses[$orc->etapa_funil]['title'] }}
                        </p>
                    </div>
                    <span class="font-bold text-green-600 dark:text-green-400">
                        R$ {{ number_format($orc->valor_efetivo, 2, ',', '.') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>