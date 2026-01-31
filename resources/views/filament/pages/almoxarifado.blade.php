<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card Produtos --}}
        <a href="{{ url('/admin/almoxarifado/produtos') }}"
            class="block p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                    <x-heroicon-o-cube class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Produtos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Catálogo de produtos</p>
                </div>
            </div>
        </a>

        {{-- Card Equipamentos --}}
        <a href="{{ url('/admin/almoxarifado/equipamentos') }}"
            class="block p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning-100 dark:bg-warning-900 rounded-lg">
                    <x-heroicon-o-wrench-screwdriver class="w-8 h-8 text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Equipamentos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ferramentas e máquinas</p>
                </div>
            </div>
        </a>

        {{-- Card Estoques --}}
        <a href="{{ url('/admin/almoxarifado/estoques') }}"
            class="block p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success-100 dark:bg-success-900 rounded-lg">
                    <x-heroicon-o-archive-box class="w-8 h-8 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Estoques</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Controle de estoque</p>
                </div>
            </div>
        </a>

        {{-- Card Lista de Desejos --}}
        <a href="{{ url('/admin/almoxarifado/lista-desejos') }}"
            class="block p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-lg">
                    <x-heroicon-o-heart class="w-8 h-8 text-danger-600 dark:text-danger-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lista de Desejos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Itens desejados</p>
                </div>
            </div>
        </a>
    </div>
</x-filament-panels::page>