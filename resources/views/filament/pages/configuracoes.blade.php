<x-filament::page>
    {{-- HUB DE NAVEGAÇÃO: Acesso rápido aos módulos de configuração --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-squares-2x2 class="w-5 h-5 text-primary-500" />
            Módulos de Configuração
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card Categorias --}}
            <a href="{{ url('/admin/categorias') }}"
                class="block p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 group">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-amber-100 dark:bg-amber-900/50 rounded-lg group-hover:scale-110 transition-transform">
                        <x-heroicon-o-tag class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Categorias</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Taxonomias do sistema</p>
                    </div>
                </div>
            </a>

            {{-- Card Tabela de Preços --}}
            <a href="{{ url('/admin/configuracoes/tabela-precos') }}"
                class="block p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 group">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-green-100 dark:bg-green-900/50 rounded-lg group-hover:scale-110 transition-transform">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Tabela de Preços</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Valores e precificação</p>
                    </div>
                </div>
            </a>

            {{-- Card Garantias --}}
            <a href="{{ url('/admin/configuracoes/garantias') }}"
                class="block p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 group">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/50 rounded-lg group-hover:scale-110 transition-transform">
                        <x-heroicon-o-shield-check class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Garantias</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Termos e condições</p>
                    </div>
                </div>
            </a>

            {{-- Card Google Calendar --}}
            <a href="{{ url('/admin/google-calendar-settings') }}"
                class="block p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 group">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-red-100 dark:bg-red-900/50 rounded-lg group-hover:scale-110 transition-transform">
                        <x-heroicon-o-calendar class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Google Calendar</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Integração e sincronização</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Separador visual --}}
    <div class="border-t border-gray-200 dark:border-gray-700 mb-6"></div>

    {{-- FORMULÁRIO DE CONFIGURAÇÕES DO SISTEMA --}}
    <form wire:submit.prevent="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" color="primary">
                Salvar Todas as Configurações
            </x-filament::button>
        </div>
    </form>
</x-filament::page>