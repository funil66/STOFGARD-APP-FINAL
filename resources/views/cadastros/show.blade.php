<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ver Cadastro</title>
    <link href="/css/app.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="p-6 bg-gray-50">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('cadastros.index') }}" class="text-sm text-blue-600 underline">&larr; Voltar</a>

        <h1 class="text-2xl font-bold mt-4">{{ $item->nome }}</h1>
        <div class="text-sm text-gray-600 mb-4">Tipo: {{ ucfirst($type) }}</div>

        <div class="bg-white p-4 rounded shadow">
            @livewire(\App\Http\Livewire\CadastroShow::class, ['uuidOrId' => $uuid ?? ($item->uuid ?? null)])
        </div>

        @if(session('success'))
            <div class="mt-4 text-green-600">{{ session('success') }}</div>
        @endif
    </div>

    @livewireScripts
</body>
</html>