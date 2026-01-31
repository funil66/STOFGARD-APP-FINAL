<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Orçamento | StofGard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">

            <!-- Header -->
            <div class="bg-blue-600 p-8 text-center">
                <h1 class="text-3xl font-bold text-white mb-2">StofGard</h1>
                <p class="text-blue-100">Especialistas em Estofados</p>
            </div>

            <!-- Form -->
            <div class="p-8">
                <h2 class="text-xl font-semibold text-center mb-6 text-gray-700">Solicite seu Orçamento Grátis</h2>

                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Recebido!</p>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('solicitar.orcamento.post') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Seu Nome</label>
                        <input type="text" name="nome" id="nome" required
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                            placeholder="Como podemos te chamar?">
                    </div>

                    <div>
                        <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                        <input type="tel" name="celular" id="celular" required
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                            placeholder="(DDD) 99999-9999">
                    </div>

                    <div>
                        <label for="servico" class="block text-sm font-medium text-gray-700 mb-1">Serviço de
                            Interesse</label>
                        <select name="servico" id="servico"
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                            <option value="higienizacao">Limpeza / Higienização</option>
                            <option value="impermeabilizacao">Impermeabilização</option>
                            <option value="combo">Combo (Limpeza + Imper)</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade / Bairro</label>
                        <input type="text" name="cidade" id="cidade" required
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                            placeholder="Ex: Curitiba - Batel">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        Solicitar Contato
                    </button>

                    <p class="text-xs text-center text-gray-400 mt-4">
                        Nossa equipe entrará em contato pelo WhatsApp em instantes.
                    </p>
                </form>
            </div>
        </div>
    </div>

</body>

</html>