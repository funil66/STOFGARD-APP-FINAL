<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha - AUTONOMIA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .ai-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 44px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 0;
            transition: opacity .2s ease;
        }

        .ai-btn:hover {
            opacity: .92;
        }

        .ai-btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .ai-btn-secondary {
            background: #0f172a;
            color: #93c5fd;
            border: 1px solid #334155;
        }

        .ai-btn-success {
            background: #052e16;
            color: #6ee7b7;
            border: 1px solid #14532d;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-900 text-white flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-gray-800 border border-gray-700 rounded-xl shadow-2xl p-8">
        <div class="text-center mb-6">
            <a href="/" class="inline-block text-2xl font-extrabold mb-2">AUTONOMIA <span class="text-emerald-400">ILIMITADA</span></a>
            <h1 class="text-xl font-bold text-blue-400 mb-2">Redefinir senha</h1>
            <p class="text-sm text-gray-300">Use o código recebido por e-mail e defina sua nova senha.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-900/50 border border-green-700 text-green-200 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('empresa.password.reset.update') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm mb-1">Empresa</label>
                <input type="text" name="empresa" value="{{ old('empresa', $empresa) }}"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('empresa') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $email) }}"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Código de confirmação</label>
                <input type="text" name="codigo" value="{{ old('codigo') }}" maxlength="6" inputmode="numeric"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('codigo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Nova senha</label>
                <input type="password" name="password"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
                @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Confirmar nova senha</label>
                <input type="password" name="password_confirmation"
                    class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <button type="submit" class="w-full py-2.5 rounded bg-blue-600 hover:bg-blue-700 font-semibold ai-btn ai-btn-primary">
                Redefinir senha
            </button>
        </form>

        <div class="mt-6 space-y-3">
            <a href="{{ route('empresa.password.reset.request') }}" class="ai-btn ai-btn-secondary text-sm">Reenviar código</a>
            <a href="{{ route('empresa.login') }}" class="ai-btn ai-btn-success text-sm">Voltar para login</a>
        </div>
    </div>
</body>
</html>
