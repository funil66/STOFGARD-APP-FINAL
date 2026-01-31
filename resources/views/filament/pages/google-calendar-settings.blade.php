<x-filament-panels::page>
    <div class="space-y-6">
        @if($token)
            {{-- Conectado --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-success-600 dark:text-success-400 font-semibold">Conectado ao Google
                            Calendar</span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    <div class="flex items-start gap-3 p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                        <svg class="w-5 h-5 text-success-600 dark:text-success-400 mt-0.5" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-success-800 dark:text-success-300">
                                Sincronização Automática Ativada
                            </p>
                            <p class="text-sm text-success-700 dark:text-success-400 mt-1">
                                Todos os seus agendamentos serão sincronizados automaticamente com o Google Calendar.
                                Qualquer alteração feita aqui será refletida no Google Calendar e vice-versa.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium text-gray-900 dark:text-gray-100">Status</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Conectado e funcionando</p>
                        </div>

                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium text-gray-900 dark:text-gray-100">Última sincronização</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $token->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">O que é sincronizado?</h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Título e descrição do agendamento</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Data e horário (início e fim)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Localização e endereço</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Lembretes e notificações</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Cores e informações do cliente/OS</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </x-filament::section>
        @else
            {{-- Não conectado --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">Google Calendar não conectado</span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    <div class="flex items-start gap-3 p-4 bg-warning-50 dark:bg-warning-950 rounded-lg">
                        <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 mt-0.5" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-warning-800 dark:text-warning-300">
                                Sincronização Desativada
                            </p>
                            <p class="text-sm text-warning-700 dark:text-warning-400 mt-1">
                                Conecte sua conta Google para sincronizar automaticamente seus agendamentos com o Google
                                Calendar.
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Benefícios da integração:</h4>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Sincronização automática:</strong> Seus agendamentos aparecem automaticamente
                                    no Google Calendar</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Acesso móvel:</strong> Visualize seus compromissos em qualquer
                                    dispositivo</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Notificações:</strong> Receba lembretes no seu celular e email</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Compartilhamento:</strong> Facilite o compartilhamento de agendas com sua
                                    equipe</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span><strong>Backup automático:</strong> Seus agendamentos ficam salvos na nuvem do
                                    Google</span>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Como funciona?</h4>
                        <ol class="space-y-2 text-sm text-gray-600 dark:text-gray-400 list-decimal list-inside">
                            <li>Clique no botão "Conectar Google Calendar" acima</li>
                            <li>Faça login com sua conta Google</li>
                            <li>Autorize o acesso ao seu calendário</li>
                            <li>Pronto! A sincronização será automática a partir de agora</li>
                        </ol>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>