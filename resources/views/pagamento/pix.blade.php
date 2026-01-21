<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold mb-1">Pagamento via PIX</h1>
                    <p class="text-blue-100 text-sm">Scaneie o QR Code ou copie o código</p>
                </div>
            </div>
            
            {{-- Content --}}
            <div class="p-6 space-y-6">
                {{-- Descrição --}}
                <div class="text-center">
                    <p class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $financeiro->descricao }}
                    </p>
                    @if($financeiro->cliente)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Cliente: {{ $financeiro->cliente->nome }}
                        </p>
                    @endif
                </div>
                
                {{-- Valor --}}
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-2">Valor a Pagar</p>
                    <p class="text-4xl font-bold text-green-600 dark:text-green-400 text-center">
                        R$ {{ number_format($financeiro->valor_total, 2, ',', '.') }}
                    </p>
                </div>
                
                {{-- QR Code --}}
                @if($financeiro->pix_qrcode_base64)
                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                    <img src="data:image/png;base64,{{ $financeiro->pix_qrcode_base64 }}" 
                         alt="QR Code PIX" 
                         class="w-full max-w-xs mx-auto">
                </div>
                @endif
                
                {{-- Código Copia e Cola --}}
                @if($financeiro->pix_copia_cola)
                <div class="space-y-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center font-medium">
                        Ou copie o código PIX:
                    </p>
                    <div class="flex gap-2">
                        <input type="text" 
                               id="pixCode" 
                               value="{{ $financeiro->pix_copia_cola }}"
                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-xs bg-gray-50 dark:bg-gray-900 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               readonly
                               onclick="this.select()">
                        <button onclick="copiarPix()" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copiar
                        </button>
                    </div>
                    <div id="copiado" class="text-xs text-green-600 dark:text-green-400 text-center font-medium hidden">
                        ✔ Código copiado!
                    </div>
                </div>
                @endif
                
                {{-- Expiração --}}
                @if($financeiro->pix_expiracao)
                <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Expira em: {{ $financeiro->pix_expiracao->format('d/m/Y \\à\\s H:i') }}</span>
                </div>
                @endif
                
                {{-- Instruções --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 space-y-2">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Como pagar:
                    </h3>
                    <ol class="text-xs text-blue-800 dark:text-blue-200 space-y-1 ml-7 list-decimal">
                        <li>Abra o app do seu banco</li>
                        <li>Escolha pagar com PIX</li>
                        <li>Escaneie o QR Code ou cole o código</li>
                        <li>Confirme o pagamento</li>
                    </ol>
                </div>
                
                {{-- Status --}}
                <div id="statusContainer" class="hidden">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-bold text-green-800 dark:text-green-300 mb-1">
                            Pagamento Confirmado!
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-400">
                            Obrigado pelo seu pagamento.
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                <p>© {{ date('Y') }} {{ config('app.name') }}. Pagamento seguro via PIX.</p>
            </div>
        </div>
    </div>
    
    <script>
        function copiarPix() {
            const input = document.getElementById('pixCode');
            input.select();
            input.setSelectionRange(0, 99999); // Mobile
            
            navigator.clipboard.writeText(input.value).then(() => {
                const msg = document.getElementById('copiado');
                msg.classList.remove('hidden');
                setTimeout(() => msg.classList.add('hidden'), 3000);
            }).catch(() => {
                document.execCommand('copy');
                const msg = document.getElementById('copiado');
                msg.classList.remove('hidden');
                setTimeout(() => msg.classList.add('hidden'), 3000);
            });
        }
        
        // Verificar status a cada 5 segundos
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('pagamento.verificar', $financeiro->link_pagamento_hash) }}');
                const data = await response.json();
                
                if (data.pago) {
                    document.getElementById('statusContainer').classList.remove('hidden');
                    // Redirecionar após 3 segundos
                    setTimeout(() => {
                        window.location.href = '{{ route('pagamento.pix', $financeiro->link_pagamento_hash) }}';
                    }, 3000);
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
            }
        }, 5000);
    </script>
</body>
</html>