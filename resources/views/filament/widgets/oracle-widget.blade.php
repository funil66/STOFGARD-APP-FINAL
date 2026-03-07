<x-filament-widgets::widget>
    <x-filament::section>

        <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4 dark:border-slate-800">
            <div
                class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-800 dark:text-white">Oráculo IA</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">O teu assistente pessoal de negócios. Pergunta-me
                    o que quiseres.</p>
            </div>
        </div>

        <div class="flex flex-col gap-4">

            <form wire:submit.prevent="askOracle" class="flex flex-col gap-3">
                <textarea wire:model.defer="question" rows="3"
                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white sm:text-sm p-4"
                    placeholder="Ex: Escreve uma mensagem educada para cobrar uma fatura atrasada ao Seu Madruga..."
                    required></textarea>

                <div class="flex justify-end mt-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-xl shadow-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-60"
                        wire:loading.attr="disabled" wire:target="askOracle">
                        <svg wire:loading wire:target="askOracle" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>

                        <span wire:loading.remove wire:target="askOracle">Consultar Oráculo</span>
                        <span wire:loading wire:target="askOracle">A processar os dados...</span>
                    </button>
                </div>
            </form>

            @if($answer)
                <div
                    class="mt-6 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-indigo-100 dark:border-slate-700 shadow-inner relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 mt-1">
                            <span class="text-3xl">🤖</span>
                        </div>
                        <div class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap font-medium">
                            {{ $answer }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </x-filament::section>
</x-filament-widgets::widget>