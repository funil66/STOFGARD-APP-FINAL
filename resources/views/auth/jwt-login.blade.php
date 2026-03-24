<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Empresa - AUTONOMIA ILIMITADA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .ai-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 46px;
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

        .login-input {
            color: #ffffff !important;
            caret-color: #ffffff;
            -webkit-text-fill-color: #ffffff !important;
        }

        .login-input::placeholder {
            color: #9ca3af !important;
            opacity: 1;
        }

        .login-input:-webkit-autofill,
        .login-input:-webkit-autofill:hover,
        .login-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff !important;
            box-shadow: 0 0 0px 1000px #374151 inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-900 text-white flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-gray-800 rounded-xl shadow-2xl p-8 border border-gray-700">
        <div class="text-center mb-8">
            <a href="/" class="inline-block text-2xl font-extrabold mb-2">AUTONOMIA <span class="text-emerald-400">ILIMITADA</span></a>
            <p class="text-gray-400 text-sm">Login da empresa para acessar o painel</p>
        </div>

        @if (request()->query('paypal') === 'approved')
            <div class="mb-4 p-3 rounded bg-emerald-900/40 border border-emerald-700 text-emerald-100 text-sm">
                Pagamento aprovado. Faça login para continuar a ativação da sua conta.
            </div>
        @elseif (request()->query('paypal') === 'cancelled')
            <div class="mb-4 p-3 rounded bg-amber-900/40 border border-amber-700 text-amber-100 text-sm">
                Checkout PayPal cancelado. Você pode tentar novamente no cadastro.
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-emerald-900/40 border border-emerald-700 text-emerald-100 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form data-jwt-login-form class="space-y-6">
            <div id="login-error-alert" class="hidden bg-red-900 border-l-4 border-red-500 text-red-100 p-4 rounded text-sm" role="alert">
                <p id="login-error-message"></p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">E-mail Profissional</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="seu@email.com"
                    class="login-input w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white outline-none transition"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                    class="login-input w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white outline-none transition"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex justify-center items-center ai-btn ai-btn-primary"
            >
                <span id="btn-text">Entrar no Sistema</span>
                <svg id="btn-spinner" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>

        <div class="mt-6 space-y-3">
            <a href="{{ route('empresa.password.reset.request') }}" class="ai-btn ai-btn-secondary text-sm">Esqueceu a senha? Recuperar acesso</a>
            <a href="{{ route('registro.empresa') }}" class="ai-btn ai-btn-success text-sm">Ainda não tem empresa? Criar cadastro</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.authApi) {
                window.authApi.bindJwtLoginForm({
                    onSuccess: (data) => {
                        console.log('Login efetuado com sucesso', data);
                        window.location.assign('/admin');
                    },
                    onError: (error) => {
                        const alertBox = document.getElementById('login-error-alert');
                        const msgBox = document.getElementById('login-error-message');

                        alertBox.classList.remove('hidden');
                        msgBox.innerText = error.message || 'Credenciais inválidas. Tente novamente.';
                        document.getElementById('btn-spinner').classList.add('hidden');
                        document.getElementById('btn-text').innerText = 'Entrar no Sistema';
                    }
                });

                const form = document.querySelector('[data-jwt-login-form]');
                form.addEventListener('submit', () => {
                    document.getElementById('login-error-alert').classList.add('hidden');
                    document.getElementById('btn-spinner').classList.remove('hidden');
                    document.getElementById('btn-text').innerText = 'Autenticando...';
                });
            } else {
                console.error('authApi não encontrado no escopo global.');
            }
        });
    </script>
</body>
</html>