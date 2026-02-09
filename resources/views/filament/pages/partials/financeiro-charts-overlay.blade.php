<div class="p-4 space-y-6">
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4 dark:bg-gray-900 dark:ring-white/10">
        @livewire('financeiro-chart')
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4 dark:bg-gray-900 dark:ring-white/10">
            @livewire('despesas-por-categoria-chart')
        </div>
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4 dark:bg-gray-900 dark:ring-white/10">
            @livewire('fluxo-caixa-chart')
        </div>
    </div>
</div>