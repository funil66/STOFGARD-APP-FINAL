<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            <a href="{{ \App\Filament\Resources\OrdemServicoResource::getUrl('create') }}"
                class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-gray-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-2xl border border-slate-200 dark:border-gray-700 hover:border-emerald-500 transition shadow-sm group">
                <div
                    class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <span class="font-bold text-slate-700 dark:text-gray-300 text-sm text-center">Nova OS</span>
            </a>

            <a href="{{ \App\Filament\Resources\OrcamentoResource::getUrl('create') }}"
                class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-2xl border border-slate-200 dark:border-gray-700 hover:border-blue-500 transition shadow-sm group">
                <div
                    class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <span class="font-bold text-slate-700 dark:text-gray-300 text-sm text-center">Criar Orçamento</span>
            </a>

            <a href="{{ \App\Filament\Resources\CadastroResource::getUrl('create') }}"
                class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-2xl border border-slate-200 dark:border-gray-700 hover:border-purple-500 transition shadow-sm group">
                <div
                    class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                        </path>
                    </svg>
                </div>
                <span class="font-bold text-slate-700 dark:text-gray-300 text-sm text-center">Novo Cliente</span>
            </a>

            <a href="{{ \App\Filament\Resources\AgendaResource::getUrl('index') }}"
                class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-2xl border border-slate-200 dark:border-gray-700 hover:border-orange-500 transition shadow-sm group">
                <div
                    class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <span class="font-bold text-slate-700 dark:text-gray-300 text-sm text-center">Agenda</span>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>