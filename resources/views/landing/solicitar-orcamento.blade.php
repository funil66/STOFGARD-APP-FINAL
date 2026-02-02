<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Solicite um orçamento gratuito para serviços de estofados - StofGard">
    <title>Solicitar Orçamento | StofGard</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            {{-- Card Principal --}}
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                {{-- Header --}}
                <div class="bg-blue-600 p-8 text-center">
                    <h1 class="text-3xl font-bold text-white mb-2">StofGard</h1>
                    <p class="text-blue-100">Especialistas em Estofados</p>
                </div>

                {{-- Formulário --}}
                <div class="p-8">
                    <h2 class="text-xl font-semibold text-center mb-6 text-gray-700">
                        Solicite seu Orçamento Grátis
                    </h2>

                    {{-- Mensagem de Sucesso --}}
                    @if(session('success'))
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg" role="alert">
                            <p class="font-bold">Recebido!</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    {{-- Mensagem de Erro --}}
                    @if(session('error'))
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg" role="alert">
                            <p class="font-bold">Erro</p>
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 rounded-lg">
                            <ul class="list-disc list-inside text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('solicitar.orcamento.post') }}" method="POST" class="space-y-5">
                        @csrf

                        {{-- Nome --}}
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                                Seu Nome
                            </label>
                            <input 
                                type="text" 
                                name="nome" 
                                id="nome" 
                                value="{{ old('nome') }}"
                                required
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                                placeholder="Como podemos te chamar?"
                            >
                        </div>

                        {{-- WhatsApp --}}
                        <div>
                            <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">
                                WhatsApp
                            </label>
                            <input 
                                type="tel" 
                                name="celular" 
                                id="celular" 
                                value="{{ old('celular') }}"
                                required
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                                placeholder="(DDD) 99999-9999"
                            >
                        </div>

                        {{-- Serviço --}}
                        <div>
                            <label for="servico" class="block text-sm font-medium text-gray-700 mb-1">
                                Serviço de Interesse
                            </label>
                            <select 
                                name="servico" 
                                id="servico"
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                            >
                                <option value="higienizacao" {{ old('servico') == 'higienizacao' ? 'selected' : '' }}>Limpeza / Higienização</option>
                                <option value="impermeabilizacao" {{ old('servico') == 'impermeabilizacao' ? 'selected' : '' }}>Impermeabilização</option>
                                <option value="combo" {{ old('servico') == 'combo' ? 'selected' : '' }}>Combo (Limpeza + Imper)</option>
                                <option value="outro" {{ old('servico') == 'outro' ? 'selected' : '' }}>Outro</option>
                            </select>
                        </div>

                        {{-- Cidade --}}
                        <div>
                            <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">
                                Cidade / Bairro
                            </label>
                            <input 
                                type="text" 
                                name="cidade" 
                                id="cidade" 
                                value="{{ old('cidade') }}"
                                required
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                                placeholder="Ex: Curitiba - Batel"
                            >
                        </div>

                        {{-- Botão Submit --}}
                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg shadow-lg hover:shadow-xl transition-all"
                        >
                            Solicitar Contato
                        </button>

                        <p class="text-xs text-center text-gray-400 mt-4">
                            Nossa equipe entrará em contato pelo WhatsApp em instantes.
                        </p>
                    </form>
                </div>
            </div>

            {{-- Footer --}}
            <p class="text-center text-gray-400 text-xs mt-6">
                © {{ date('Y') }} StofGard. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>