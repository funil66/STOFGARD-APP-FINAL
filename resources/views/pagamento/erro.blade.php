<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                Ops! Algo deu errado
            </h1>
            
            <p class="text-gray-600 mb-6">
                {{ $mensagem ?? 'Erro ao processar pagamento.' }}
            </p>
            
            <div class="bg-red-50 rounded-lg p-4 text-sm text-red-800">
                <p class="mb-2">Se o problema persistir, entre em contato conosco.</p>
            </div>
        </div>
    </div>
</body>
</html>