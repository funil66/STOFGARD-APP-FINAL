<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - AUTONOMIA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-900 text-white flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-gray-800 border border-gray-700 rounded-xl shadow-2xl p-8">
        <div class="text-center mb-6">
            <a href="/" class="inline-block text-2xl font-extrabold mb-2">AUTONOMIA <span class="text-emerald-400">ILIMITADA</span></a>
            <h1 class="text-xl font-bold text-blue-400 mb-2">Recuperar acesso</h1>
            <p class="text-sm text-gray-300">Informe a empresa e o e-mail para receber o código.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-900/50 border border-green-700 text-green-200 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('empresa.password.reset.send-code') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm mb-1">Empresa (subdomínio ou domínio)</label>
                <input type="text" name="empresa" value="{{ old('empresa') }}" placeholder="microsoft ou microsoft.autonomia.app.br"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('empresa') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full py-2.5 rounded bg-blue-600 hover:bg-blue-700 font-semibold">
                Enviar código de recuperação
            </button>
        </form>

        <div class="mt-6 text-center space-y-2">
            <a href="{{ route('empresa.login') }}" class="block text-sm text-blue-300 hover:text-blue-200">Voltar para login</a>
            <a href="{{ route('registro.empresa') }}" class="block text-sm text-emerald-300 hover:text-emerald-200">Criar nova empresa</a>
        </div>
    </div>
</body>
</html>
