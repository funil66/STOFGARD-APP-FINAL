<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pagamento Confirmado - {{ config('app.name') }}">
    <title>Pagamento Confirmado | {{ config('app.name') }}</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-8 text-center">
            {{-- Ícone de Sucesso --}}
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            {{-- Título --}}
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Pagamento Confirmado!
            </h1>
            
            <p class="text-gray-600 mb-6">
                Seu pagamento foi processado com sucesso.
            </p>
            
            {{-- Detalhes do Pagamento --}}
            <div class="bg-green-50 rounded-lg p-6 mb-6">
                <div class="space-y-3 text-left">
                    <div class="flex justify-between items-center py-2 border-b border-green-100">
                        <span class="text-gray-600 text-sm">Descrição:</span>
                        <span class="font-semibold text-gray-800 text-sm">{{ $financeiro->descricao }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-2 border-b border-green-100">
                        <span class="text-gray-600 text-sm">Valor:</span>
                        <span class="font-bold text-green-600 text-lg">
                            R$ {{ number_format($financeiro->valor_pago ?? $financeiro->valor_total, 2, ',', '.') }}
                        </span>
                    </div>
                    
                    @if($financeiro->data_pagamento)
                    <div class="flex justify-between items-center py-2 border-b border-green-100">
                        <span class="text-gray-600 text-sm">Data:</span>
                        <span class="font-semibold text-gray-800 text-sm">
                            {{ $financeiro->data_pagamento->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    @endif
                    
                    @if($financeiro->forma_pagamento)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600 text-sm">Forma:</span>
                        <span class="font-semibold text-gray-800 text-sm uppercase">
                            {{ $financeiro->forma_pagamento }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Mensagem de Agradecimento --}}
            <p class="text-sm text-gray-500">
                Obrigado pela preferência!
            </p>
            
            {{-- Footer --}}
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-400">
                    © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>