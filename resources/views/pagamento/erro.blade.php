<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Erro no pagamento - {{ config('app.name') }}">
    <title>Erro | {{ config('app.name') }}</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-8 text-center">
            {{-- Ícone de Erro --}}
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            {{-- Título --}}
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                Ops! Algo deu errado
            </h1>
            
            {{-- Mensagem de Erro --}}
            <p class="text-gray-600 mb-6">
                {{ $mensagem ?? 'Erro ao processar pagamento.' }}
            </p>
            
            {{-- Instruções --}}
            <div class="bg-red-50 rounded-lg p-4 text-sm text-red-800 mb-6">
                <p class="mb-2 font-medium">O que você pode fazer:</p>
                <ul class="text-left list-disc list-inside space-y-1">
                    <li>Verifique sua conexão com a internet</li>
                    <li>Tente novamente em alguns minutos</li>
                    <li>Entre em contato com nosso suporte</li>
                </ul>
            </div>
            
            {{-- Botão Voltar --}}
            <a href="javascript:history.back()" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                ← Voltar
            </a>
            
            {{-- Footer --}}
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-400">
                    Se o problema persistir, entre em contato conosco.
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>