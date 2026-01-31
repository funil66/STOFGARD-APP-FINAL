<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Informações do Sistema --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                    <span class="text-lg font-semibold">Sistema Stofgard 2026</span>
                </div>

                <div class="flex items-center gap-3 mt-3">
                    <a href="{{ route('filament.admin.pages.configuracoes-gerais') }}"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2" />
                        </svg>
                        Configurações Gerais
                    </a>

                    <a href="{{ admin_resource_route('filament.admin.resources.configuracaos.index', '/admin/configuracoes') }}"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-50 bg-gray-900 border border-gray-800 rounded-lg hover:bg-gray-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v8m-4-4h8m5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Configurações Avançadas
                    </a>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Versão</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">v1.0.0</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Data de Lançamento</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">01/01/2026</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-gray-100">Laravel</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ app()->version() }}</p>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-gray-100">PHP</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ PHP_VERSION }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Módulos Disponíveis --}}
        <x-filament::section>
            <x-slot name="heading">
                Módulos do Sistema
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Clientes</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Gestão completa de clientes</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Ordens de Serviço</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Controle de OS e garantias</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Agenda</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Calendário e sincronização Google
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Orçamentos</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Geração e conversão em OS</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Parceiros</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Gestão de parceiros e comissões
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Financeiro</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Controle de receitas e despesas
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-success-800 dark:text-success-300">Almoxarifado</p>
                        <p class="text-xs text-success-700 dark:text-success-400 mt-1">Estoque, produtos e movimentações
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Integrações --}}
        <x-filament::section>
            <x-slot name="heading">
                Integrações Externas
            </x-slot>

            <div class="space-y-4">
                <div
                    class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-8 h-8 text-primary-600" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">Google Calendar</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sincronização automática de
                                agendamentos</p>
                            <a href="{{ route('filament.admin.pages.google-calendar-settings') }}"
                                class="text-sm text-primary-600 hover:text-primary-700 mt-2 inline-block">
                                Configurar integração →
                            </a>
                        </div>
                    </div>
                    <span
                        class="px-3 py-1 text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300 rounded-full">
                        Disponível
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- Sobre --}}
        <x-filament::section>
            <x-slot name="heading">
                Sobre o Sistema
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-700 dark:text-gray-300">
                    O <strong>Sistema Stofgard 2026</strong> é uma solução completa para gestão de empresas de
                    impermeabilização e higienização.
                    Desenvolvido com tecnologias modernas (Laravel + Filament), oferece controle total sobre clientes,
                    ordens de serviço,
                    agendamentos, orçamentos, financeiro e estoque.
                </p>

                <div
                    class="mt-4 p-4 bg-primary-50 dark:bg-primary-950 rounded-lg border border-primary-200 dark:border-primary-800">
                    <p class="text-sm text-primary-900 dark:text-primary-100 font-medium mb-2">
                        🚀 Desenvolvido em Janeiro de 2026
                    </p>
                    <p class="text-sm text-primary-800 dark:text-primary-200">
                        Todos os direitos reservados © Stofgard 2026
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>