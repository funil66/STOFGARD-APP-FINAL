{{-- ARQUIVO: resources/views/auth/jwt-login.blade.php --}}
{{-- DESCRIÇÃO: Tela de Autenticação Blindada do Prestador (SaaS Autonomia) --}}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Autonomia PRO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white h-screen flex items-center justify-center">

    <div class="max-w-md w-full bg-gray-800 rounded-xl shadow-2xl p-8 border border-gray-700">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-500 mb-2">AUTONOMIA APP</h1>
            <p class="text-gray-400 text-sm">Acesse o seu Quartel General</p>
        </div>

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
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white outline-none transition"
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
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white outline-none transition"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex justify-center items-center"
            >
                <span id="btn-text">Entrar no Sistema</span>
                <svg id="btn-spinner" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>

        </form>

        <div class="mt-6 text-center">
            <a href="#" class="text-sm text-blue-400 hover:text-blue-300">Esqueceu a senha? Chora não. Clica aqui.</a>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Verifica se a IA carregou o authApi certinho no window
            if (window.authApi) {
                window.authApi.bindJwtLoginForm({
                    // O que acontece se o login der sucesso?
                    onSuccess: (data) => {
                        console.log('Login efetuado com sucesso! Token gerado.', data);
                        // Redireciona o peão pro painel principal dele (Ajuste essa rota pro seu painel Filament ou Vue)
                        window.location.assign('/admin');
                    },
                    // O que acontece se errar a senha ou der erro 500?
                    onError: (error) => {
                        console.error('Erro no login:', error);
                        const alertBox = document.getElementById('login-error-alert');
                        const msgBox = document.getElementById('login-error-message');

                        alertBox.classList.remove('hidden');
                        msgBox.innerText = error.message || 'Credenciais inválidas. Tenta de novo, amigão.';

                        // Reseta o botão de loading
                        document.getElementById('btn-spinner').classList.add('hidden');
                        document.getElementById('btn-text').innerText = 'Entrar no Sistema';
                    }
                });

                // Fru-fru de UX: Adiciona o loading visual no click do formulário
                const form = document.querySelector('[data-jwt-login-form]');
                form.addEventListener('submit', () => {
                    document.getElementById('login-error-alert').classList.add('hidden');
                    document.getElementById('btn-spinner').classList.remove('hidden');
                    document.getElementById('btn-text').innerText = 'Autenticando...';
                });
            } else {
                console.error("ERRO CRÍTICO: authApi não encontrado. O build do Vite rodou direito?");
            }
        });
    </script>
</body>
</html>