<div class="fade-in">

    {{-- Barra de progresso --}}
    <div class="flex items-center gap-2 mb-8">
        @foreach([1 => '📅 Data', 2 => '⏰ Horário', 3 => '📋 Dados', 4 => '💳 Pagamento', 5 => '✅ Confirmado'] as $num => $label)
            <div class="flex-1 {{ $loop->last ? '' : 'flex items-center gap-1' }}">
                <div class="flex flex-col items-center gap-1">
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all
                            {{ $step > $num ? 'bg-brand text-white' : ($step === $num ? 'bg-brand text-white ring-4 ring-brand/20' : 'bg-gray-200 text-gray-500') }}">
                        {{ $step > $num ? '✓' : $num }}
                    </div>
                    <span class="text-xs {{ $step >= $num ? 'text-brand font-medium' : 'text-gray-400' }} hidden sm:block">
                        {{ $label }}
                    </span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 h-0.5 mt-4 {{ $step > $num ? 'bg-brand' : 'bg-gray-200' }} transition-all"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Erro --}}
    @if($erro)
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
            <span class="text-red-500 text-xl">⚠️</span>
            <p class="text-red-700 text-sm">{{ $erro }}</p>
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- STEP 1: Escolher Data --}}
    {{-- ======================================================= --}}
    @if($step === 1)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 fade-in">
            <h2 class="text-xl font-semibold text-gray-900 mb-1">Escolha uma data</h2>
            <p class="text-gray-500 text-sm mb-6">Selecione o dia que funciona melhor para você</p>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($diasDisponiveis as $dia)
                    <button wire:click="selecionarData('{{ $dia['data'] }}')"
                        class="group flex flex-col items-center gap-1 p-4 rounded-xl border-2 transition-all hover:border-brand hover:bg-brand/5
                                       {{ $dataSelecionada === $dia['data'] ? 'border-brand bg-brand/10' : 'border-gray-200' }}">
                        <span
                            class="text-xs text-gray-400 capitalize group-hover:text-brand">{{ substr($dia['dia_semana'], 0, 3) }}</span>
                        <span class="text-2xl font-bold text-gray-800 group-hover:text-brand">{{ $dia['dia_mes'] }}</span>
                        <span
                            class="text-xs text-gray-400 capitalize group-hover:text-brand">{{ substr($dia['mes'], 0, 3) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- STEP 2: Escolher Horário --}}
    {{-- ======================================================= --}}
    @if($step === 2)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 fade-in">
            <div class="flex items-center gap-3 mb-6">
                <button wire:click="voltarParaData" class="text-gray-400 hover:text-brand transition-colors">
                    ← Voltar
                </button>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Escolha o horário</h2>
                    <p class="text-gray-500 text-sm">
                        {{ \Carbon\Carbon::parse($dataSelecionada)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                @foreach($slotsDisponiveis as $slot)
                    @if($slot['disponivel'])
                        <button wire:click="selecionarSlot('{{ $slot['inicio'] }}', '{{ $slot['fim'] }}')" class="py-3 px-4 rounded-xl border-2 border-gray-200 hover:border-brand hover:bg-brand/5 transition-all
                                               font-semibold text-gray-800 hover:text-brand text-sm">
                            {{ $slot['hora'] }}
                        </button>
                    @else
                        <div class="py-3 px-4 rounded-xl bg-gray-50 border-2 border-gray-100 text-center">
                            <span class="text-gray-300 text-sm font-medium">{{ $slot['hora'] }}</span>
                            <div class="text-xs text-gray-300">Ocupado</div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if(empty(array_filter($slotsDisponiveis, fn($s) => $s['disponivel'])))
                <div class="text-center py-8 text-gray-500">
                    <p class="text-4xl mb-2">😕</p>
                    <p class="font-medium">Nenhum horário disponível neste dia.</p>
                    <button wire:click="voltarParaData" class="mt-3 text-brand text-sm font-medium hover:underline">
                        Escolher outra data
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- STEP 3: Dados do Cliente --}}
    {{-- ======================================================= --}}
    @if($step === 3)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 fade-in">
            <div class="flex items-center gap-3 mb-6">
                <button wire:click="voltarParaSlots" class="text-gray-400 hover:text-brand transition-colors">
                    ← Voltar
                </button>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Seus dados</h2>
                    <p class="text-gray-500 text-sm">
                        {{ \Carbon\Carbon::parse($slotInicio)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM') }}
                        às {{ \Carbon\Carbon::parse($slotInicio)->format('H:i') }}
                    </p>
                </div>
            </div>

            <form wire:submit="confirmarDados" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo *</label>
                    <input wire:model="clienteNome" type="text"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:border-brand transition-all"
                        placeholder="João Silva">
                    @error('clienteNome') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp / Telefone *</label>
                    <input wire:model="clienteTelefone" type="tel"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:border-brand transition-all"
                        placeholder="(11) 99999-9999">
                    @error('clienteTelefone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail (opcional)</label>
                    <input wire:model="clienteEmail" type="email"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:border-brand transition-all"
                        placeholder="joao@exemplo.com">
                    @error('clienteEmail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">O que você precisa? (opcional)</label>
                    <textarea wire:model="clienteObservacao" rows="3"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:border-brand transition-all resize-none"
                        placeholder="Descreva brevemente o serviço que você precisa..."></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-brand text-white py-4 px-6 rounded-xl font-semibold text-base hover:opacity-90 active:scale-[0.99] transition-all flex items-center justify-center gap-2"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70">
                    <span wire:loading.remove>Continuar para Pagamento →</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Reservando slot...
                    </span>
                </button>
            </form>
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- STEP 4: Pagamento PIX --}}
    {{-- ======================================================= --}}
    @if($step === 4)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 fade-in">
            <div class="text-center mb-6">
                <div class="text-5xl mb-3">💚</div>
                <h2 class="text-xl font-semibold text-gray-900">Pague o sinal para confirmar</h2>
                <p class="text-gray-500 text-sm mt-1">Seu slot está reservado por <strong>30 minutos</strong></p>
            </div>

            @if($valorSinal)
                <div class="bg-gray-50 rounded-xl p-4 text-center mb-4">
                    <p class="text-sm text-gray-500">Valor do sinal</p>
                    <p class="text-3xl font-bold text-gray-900">
                        R$ {{ number_format((float) $valorSinal, 2, ',', '.') }}
                    </p>
                </div>
            @endif

            @if($pixCopiaCola)
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                    <p class="text-sm font-medium text-green-800 mb-2">PIX Copia e Cola:</p>
                    <div class="bg-white rounded-lg p-3 break-all text-xs text-gray-600 font-mono border border-green-100">
                        {{ $pixCopiaCola }}
                    </div>
                    <button data-copy="{{ $pixCopiaCola }}"
                        class="mt-3 w-full bg-green-600 text-white py-3 px-4 rounded-xl font-medium text-sm hover:bg-green-700 transition-all">
                        📋 Copiar PIX
                    </button>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4 text-center">
                    <p class="text-yellow-800 text-sm">
                        ⚠️ Gateway não configurado. Entre em contato pelo WhatsApp para confirmar o agendamento.
                    </p>
                </div>
            @endif

            <div class="flex items-center gap-3 bg-blue-50 rounded-xl p-4">
                <div class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <p class="text-blue-700 text-sm">Aguardando confirmação do pagamento...</p>
            </div>

            <p class="text-center text-xs text-gray-400 mt-4">
                A confirmação é automática após o pagamento ser processado.
            </p>
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- STEP 5: Confirmado ✅ --}}
    {{-- ======================================================= --}}
    @if($step === 5)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center fade-in">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Agendamento Confirmado!</h2>
            <p class="text-gray-500 mb-6">
                Seu agendamento está confirmado. Você receberá uma mensagem no WhatsApp.
            </p>

            @if($slotInicio)
                <div class="bg-brand/10 rounded-xl p-4 mb-6 inline-block">
                    <p class="text-lg font-semibold text-brand">
                        📅 {{ \Carbon\Carbon::parse($slotInicio)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM') }}
                    </p>
                    <p class="text-brand font-medium">
                        ⏰ {{ \Carbon\Carbon::parse($slotInicio)->format('H:i') }} —
                        {{ \Carbon\Carbon::parse($slotFim)->format('H:i') }}
                    </p>
                </div>
            @endif

            <p class="text-gray-500 text-sm">
                Qualquer dúvida, entre em contato pelo WhatsApp. Até logo! 👋
            </p>
        </div>
    @endif

</div>