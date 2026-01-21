<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Pagamento Confirmado!
            </h1>
            
            <p class="text-gray-600 mb-6">
                Seu pagamento foi processado com sucesso.
            </p>
            
            <div class="bg-green-50 rounded-xl p-6 mb-6">
                <div class="space-y-2 text-left">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Descrição:</span>
                        <span class="font-semibold">{{ $financeiro->descricao }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Valor:</span>
                        <span class="font-bold text-green-600">
                            R$ {{ number_format($financeiro->valor_pago ?? $financeiro->valor_total, 2, ',', '.') }}
                        </span>
                    </div>
                    
                    @if($financeiro->data_pagamento)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data:</span>
                        <span class="font-semibold">
                            {{ $financeiro->data_pagamento->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    @endif
                    
                    @if($financeiro->forma_pagamento)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Forma:</span>
                        <span class="font-semibold uppercase">
                            {{ $financeiro->forma_pagamento }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            
            <p class="text-sm text-gray-500">
                Obrigado pela preferência!
            </p>
        </div>
    </div>
</body>
</html>