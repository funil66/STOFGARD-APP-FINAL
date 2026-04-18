<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden transition-all duration-500">
        
        @if($isPaid)
            <!-- ESTADO 2: SUCESSO (PAGO) -->
            <div class="p-10 text-center bg-gradient-to-b from-green-50 to-white animate-fade-in-up">
                <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-200">
                    <svg class="w-12 h-12 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-black text-gray-800 mb-2">Pagamento Aprovado!</h2>
                <p class="text-gray-500 mb-8 font-medium">Sua ordem de serviço foi liberada.</p>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-green-100">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold mb-1">Valor Recebido</p>
                    <p class="text-3xl font-black text-green-600">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</p>
                </div>
            </div>
        @else
            <!-- ESTADO 1: PENDENTE (AGUARDANDO PAGAMENTO) -->
            <div class="p-8" wire:poll.5s="checkPaymentStatus">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800">Orçamento #{{ $orcamento->id }}</h2>
                    <p class="text-blue-600 font-semibold text-sm mt-1 animate-pulse">Aguardando Pagamento PIX...</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-center border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold mb-1">Valor Total</p>
                    <p class="text-4xl font-black text-gray-900">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</p>
                </div>

                @if(!empty($payloadCode))
                    <div class="mb-6" x-data="{ payload: '{{ $payloadCode }}', copied: false }">
                        <!-- QR Code Gerado via API Externa para Fricção Zero -->
                        <div class="flex justify-center mb-6 relative">
                            <div class="p-3 bg-white rounded-2xl shadow-md border border-gray-100 inline-block">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($payloadCode) }}&margin=0" alt="QR Code PIX" class="w-48 h-48 rounded-xl pointer-events-none">
                            </div>
                        </div>

                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 text-center">Pix Copia e Cola</p>
                        <div class="flex rounded-xl shadow-sm overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-all">
                            <input type="text" readonly :value="payload" class="flex-1 block w-full bg-gray-50 text-gray-600 font-mono text-xs p-4 outline-none border-none" />
                            <button type="button" @click="navigator.clipboard.writeText(payload); copied = true; setTimeout(() => copied = false, 2500)" class="inline-flex items-center px-6 bg-gray-900 text-sm font-bold text-white hover:bg-black focus:outline-none transition-colors">
                                <span x-show="!copied">COPIAR</span>
                                <span x-show="copied" class="text-green-400">COPIADO!</span>
                            </button>
                        </div>
                    </div>
                    
                    @if(session()->has('error'))
                        <div class="text-red-500 text-sm text-center font-semibold mt-4">
                            {{ session('error') }}
                        </div>
                    @endif
                @else
                    <!-- SKELETON LOADER -->
                    <div class="flex flex-col items-center justify-center py-8">
                        <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-center text-sm font-semibold text-gray-500">Conectando ao Banco Central...</p>
                    </div>
                @endif
                
                <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-center space-x-2 text-xs font-semibold text-gray-400">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15v-2H9v-2h2v-2H9V9h2V7h2v2h2v2h-2v2h2v2h-2v2h-2z"/></svg>
                    <span>Ambiente Seguro 256-bits SSL</span>
                </div>
            </div>
        @endif
    </div>
</div>
