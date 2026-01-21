<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Cadastro</title>
    <link href="/css/app.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="p-6 bg-gray-50">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('cadastros.show', ['uuid' => $item->uuid]) }}" class="text-sm text-blue-600 underline">&larr; Voltar</a>

        <h1 class="text-2xl font-bold mt-4">Editar: {{ $item->nome }}</h1>

        <div class="mt-4">
            @livewire(\App\Http\Livewire\CadastroEdit::class, ['uuid' => $item->uuid])
        </div>

        @if($errors->any())
            <div class="mt-4 text-red-600">{{ implode(' ', $errors->all()) }}</div>
        @endif

        @if(session('success'))
            <div class="mt-4 text-green-600">{{ session('success') }}</div>
        @endif

    </div>

    @livewireScripts
</body>
</html>