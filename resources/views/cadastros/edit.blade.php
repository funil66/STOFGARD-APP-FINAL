<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Editar Cadastro - StofGard">
    <title>Editar: {{ $item->nome ?? 'Cadastro' }} | StofGard</title>
    <link href="/css/app.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto p-6">
        {{-- Componente Livewire --}}
        @livewire(\App\Http\Livewire\CadastroEdit::class, ['uuid' => $item->uuid])

        {{-- Mensagens de Erro --}}
        @if($errors->any())
            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-red-700 font-medium">Ocorreram erros:</p>
                        <ul class="mt-1 list-disc list-inside text-sm text-red-600">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Mensagem de Sucesso --}}
        @if(session('success'))
            <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif
    </div>

    @livewireScripts
</body>
</html>