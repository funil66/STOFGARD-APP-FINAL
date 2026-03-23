<div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center py-10 px-4">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <a href="/" class="inline-block text-2xl font-extrabold mb-2 text-slate-900">AUTONOMIA <span class="text-emerald-600">ILIMITADA</span></a>
            <h1 class="text-3xl font-bold text-gray-900">Cadastro da Empresa</h1>
            <p class="mt-2 text-gray-600">Ative seu ambiente com {{ env('TRIAL_DAYS', 14) }} dias de teste</p>
        </div>

        @if ($concluido)
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <div class="text-5xl mb-4">🎉</div>
                <h2 class="text-2xl font-bold text-green-600 mb-2">Empresa criada com sucesso!</h2>
                <p class="text-gray-600 mb-4">Seu painel está disponível em:</p>
                <a href="//{{ $dominio_criado }}/admin"
                   class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition">
                    Acessar {{ $dominio_criado }}
                </a>

                @if ($status_assinatura_url)
                    <div class="mt-3">
                        <a href="{{ $status_assinatura_url }}"
                           class="inline-block text-sm text-emerald-700 hover:text-emerald-800 font-medium underline">
                            Acompanhar status da assinatura
                        </a>
                    </div>
                @endif

                @if ($erro_assinatura)
                    <div class="mt-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                        {{ $erro_assinatura }}
                    </div>
                @endif

                @if ($checkout_url || $boleto_url || $pix_copia_cola)
                    <div class="mt-6 p-4 rounded-lg bg-blue-50 border border-blue-200 text-left">
                        <p class="text-sm font-semibold text-blue-800 mb-3">Finalize sua assinatura</p>

                        @if ($checkout_url)
                            <a href="{{ $checkout_url }}" target="_blank" rel="noopener"
                               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                                Ir para checkout
                            </a>
                        @endif

                        @if ($boleto_url)
                            <div class="mt-3">
                                <a href="{{ $boleto_url }}" target="_blank" rel="noopener"
                                   class="text-sm font-medium text-blue-700 hover:text-blue-800 underline">
                                    Abrir boleto
                                </a>
                            </div>
                        @endif

                        @if ($pix_copia_cola)
                            <div class="mt-3">
                                <p class="text-xs text-blue-700 mb-1">PIX copia e cola:</p>
                                <textarea readonly rows="3" class="w-full rounded-md border border-blue-200 bg-white text-xs text-gray-700">{{ $pix_copia_cola }}</textarea>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @else
            {{-- Progress Steps --}}
            <div class="flex justify-center mb-8">
                @foreach ([1 => 'Empresa', 2 => 'Administrador', 3 => 'Plano', 4 => 'Verificação', 5 => 'Confirmação'] as $num => $label)
                    <div class="flex items-center">
                        <div class="flex flex-col items-center">
                            <div @class([
                                'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2',
                                'bg-emerald-600 text-white border-emerald-600' => $step >= $num,
                                'bg-white text-gray-400 border-gray-300' => $step < $num,
                            ])>{{ $num }}</div>
                            <span class="text-xs mt-1 {{ $step >= $num ? 'text-emerald-600' : 'text-gray-400' }}">{{ $label }}</span>
                        </div>
                        @if ($num < 5)
                            <div @class(['w-12 h-0.5 mx-2 mt-[-12px]', 'bg-emerald-600' => $step > $num, 'bg-gray-300' => $step <= $num])></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-xl shadow-lg p-8 border border-slate-200">
                {{-- Step 1: Dados da Empresa --}}
                @if ($step === 1)
                    <h2 class="text-xl font-semibold mb-6">🏢 Dados da Empresa</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome da Empresa *</label>
                            <input type="text" wire:model="empresa_nome" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('empresa_nome') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                            <input type="text" wire:model="empresa_cnpj" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-mail da Empresa *</label>
                            <input type="email" wire:model="empresa_email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('empresa_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" wire:model="empresa_telefone" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <p class="text-xs text-gray-500">O subdomínio será criado automaticamente com base no nome da empresa (ex.: microsoft.autonomia.app.br).</p>
                    </div>
                @endif

                {{-- Step 2: Admin User --}}
                @if ($step === 2)
                    <h2 class="text-xl font-semibold mb-6">👤 Administrador</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome Completo *</label>
                            <input type="text" wire:model="admin_nome" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('admin_nome') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-mail *</label>
                            <input type="email" wire:model="admin_email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('admin_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Senha *</label>
                            <input type="password" wire:model="admin_password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('admin_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmar Senha *</label>
                            <input type="password" wire:model="admin_password_confirmation" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                @endif

                {{-- Step 3: Plan Selection --}}
                @if ($step === 3)
                    <h2 class="text-xl font-semibold mb-6">📦 Escolha seu Plano</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach ([
                            'start' => ['label' => 'Start', 'price' => 'R$ ' . env('PLAN_START_PRICE', env('PLAN_FREE_PRICE', 49)), 'desc' => 'Até 30 OS/mês, 3 usuários'],
                            'pro' => ['label' => 'Pro', 'price' => 'R$ ' . env('PLAN_PRO_PRICE', 97), 'desc' => 'OS ilimitadas, 10 usuários'],
                            'elite' => ['label' => 'Elite', 'price' => 'R$ ' . env('PLAN_ELITE_PRICE', 197), 'desc' => 'Tudo ilimitado, suporte prioritário'],
                        ] as $key => $plan)
                            <label wire:click="$set('plano', '{{ $key }}')"
                                @class([
                                    'cursor-pointer rounded-xl border-2 p-6 text-center transition',
                                    'border-emerald-600 bg-emerald-50 ring-2 ring-emerald-200' => $plano === $key,
                                    'border-gray-200 hover:border-gray-300' => $plano !== $key,
                                ])>
                                <div class="text-lg font-bold">{{ $plan['label'] }}</div>
                                <div class="text-2xl font-extrabold text-emerald-600 my-2">{{ $plan['price'] }}<span class="text-sm text-gray-500">/mês</span></div>
                                <div class="text-sm text-gray-600">{{ $plan['desc'] }}</div>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Método de pagamento da assinatura</label>
                        <select wire:model="metodo_pagamento"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="CREDIT_CARD">Cartão de Crédito</option>
                            <option value="PIX">PIX</option>
                            <option value="BOLETO">Boleto</option>
                            <option value="PAYPAL">PayPal</option>
                        </select>
                        @error('metodo_pagamento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Step 4: Email verification --}}
                @if ($step === 4)
                    <h2 class="text-xl font-semibold mb-6">📩 Verificação de E-mail</h2>
                    <div class="space-y-4 text-sm">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 text-blue-900">
                            Enviamos um código de 6 dígitos para <strong>{{ $empresa_email }}</strong>.
                            <br>Digite o código para liberar a criação da empresa.
                        </div>

                        @if ($email_codigo_mensagem)
                            <div class="p-3 rounded border {{ $email_verificado ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                                {{ $email_codigo_mensagem }}
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código de confirmação *</label>
                            <input type="text" wire:model="email_codigo" maxlength="6" inputmode="numeric"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            @error('email_codigo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="button" wire:click="reenviarCodigoVerificacao"
                                class="text-sm text-emerald-700 font-medium underline hover:text-emerald-800">
                            Reenviar código
                        </button>
                    </div>
                @endif

                {{-- Step 5: Confirmation --}}
                @if ($step === 5)
                    <h2 class="text-xl font-semibold mb-6">✅ Confirme os Dados</h2>
                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 gap-2 p-4 bg-gray-50 rounded-lg">
                            <div class="font-medium text-gray-500">Empresa:</div>
                            <div>{{ $empresa_nome }}</div>
                            <div class="font-medium text-gray-500">CNPJ:</div>
                            <div>{{ $empresa_cnpj ?: '—' }}</div>
                            <div class="font-medium text-gray-500">E-mail empresa:</div>
                            <div>{{ $empresa_email }}</div>
                            <div class="font-medium text-gray-500">Telefone:</div>
                            <div>{{ $empresa_telefone ?: '—' }}</div>
                            <div class="font-medium text-gray-500">Domínio:</div>
                            <div>Gerado automaticamente pelo nome da empresa</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 p-4 bg-gray-50 rounded-lg">
                            <div class="font-medium text-gray-500">Administrador:</div>
                            <div>{{ $admin_nome }}</div>
                            <div class="font-medium text-gray-500">E-mail admin:</div>
                            <div>{{ $admin_email }}</div>
                        </div>
                        <div class="p-4 bg-emerald-50 rounded-lg text-center">
                            @php
                                $metodoLabel = match ($metodo_pagamento) {
                                    'CREDIT_CARD' => 'Cartão de Crédito',
                                    'PIX' => 'PIX',
                                    'BOLETO' => 'Boleto',
                                    'PAYPAL' => 'PayPal',
                                    default => $metodo_pagamento,
                                };
                            @endphp
                            <div class="font-medium">Plano: <strong class="text-emerald-600">{{ ucfirst($plano) }}</strong></div>
                            <div class="text-gray-500">Pagamento: {{ $metodoLabel }}</div>
                            <div class="text-gray-500">{{ env('TRIAL_DAYS', 14) }} dias grátis e assinatura já preparada para ativação</div>
                        </div>
                    </div>
                @endif

                {{-- Navigation Buttons --}}
                <div class="flex justify-between mt-8">
                    @if ($step > 1)
                        <button wire:click="previousStep" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            ← Voltar
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if ($step < 5)
                        <button wire:click="nextStep" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                            @if ($step === 3)
                                Enviar código →
                            @elseif ($step === 4)
                                Validar código →
                            @else
                                Próximo →
                            @endif
                        </button>
                    @else
                        <button wire:click="confirmar" wire:loading.attr="disabled"
                                class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition disabled:opacity-50">
                            <span wire:loading.remove>🚀 Criar Minha Empresa</span>
                            <span wire:loading>Criando...</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
