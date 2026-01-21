<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastros</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="p-6 bg-gray-50">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Cadastros</h1>

        <div class="mb-4 flex space-x-2">
            <a href="{{ route('cadastros.index') }}" class="px-3 py-2 rounded {{ request()->routeIs('cadastros.index') ? 'bg-blue-600 text-white' : 'bg-white border' }}">Todos</a>
            <a href="{{ route('cadastros.lojas') }}" class="px-3 py-2 rounded {{ request()->routeIs('cadastros.lojas') ? 'bg-blue-600 text-white' : 'bg-white border' }}">Lojas</a>
            <a href="{{ route('cadastros.vendedores') }}" class="px-3 py-2 rounded {{ request()->routeIs('cadastros.vendedores') ? 'bg-blue-600 text-white' : 'bg-white border' }}">Vendedores</a>
        </div>

        <form method="GET" action="{{ route('cadastros.index') }}" class="mb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Pesquisar..." class="border rounded px-3 py-2 w-72"> 
            <button class="ml-2 px-3 py-2 bg-blue-600 text-white rounded">Buscar</button>
        </form>

        <form id="cadastro-form">
            <div class="bg-white shadow rounded divide-y">
                @forelse($cadastros as $c)
                    @php
                        $under = $c->underlying_model ?? null;
                        // Use public uuid when available, otherwise fall back to numeric id (legacy records)
                        $linkId = $under?->uuid ?? ($under?->id ?? null);
                    @endphp

                    <div class="p-4 hover:bg-gray-50 flex justify-between items-center">
                        <div class="flex items-center">
                            @auth
                                <input type="radio" name="selected" value="{{ $linkId }}" class="mr-3 cadastro-radio">
                            @endauth
                            <div>
                                @if(! empty($linkId))
                                    <a href="{{ route('cadastros.show', ['uuid' => $linkId]) }}" class="font-semibold text-blue-600 hover:underline">{{ $c->nome }}</a>
                                @else
                                    <div class="font-semibold">{{ $c->nome }}</div>
                                @endif
                                <div class="text-sm text-gray-500">{{ $c->email ?? ($c->celular ?? '') }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400">{{ ucfirst($c->tipo) }}</div>
                    </div>
                @empty
                    <div class="p-4 text-gray-500">Nenhum cadastro encontrado.</div>
                @endforelse
            </div>

            @auth
                <div class="mt-4 flex space-x-2">
                    <button type="button" id="btn-view" class="px-4 py-2 bg-blue-600 text-white rounded" disabled>Visualizar</button>

                    @if(auth()->user()->email === 'allisson@stofgard.com.br')
                        <button type="button" id="btn-edit" class="px-4 py-2 bg-yellow-500 text-white rounded" disabled>Editar</button>
                    @endif

                    <button type="button" id="btn-download" class="px-4 py-2 bg-green-600 text-white rounded" disabled>Download</button>
                </div>
            @endauth
        </form>

        <div class="mt-4">{{ $cadastros->withQueryString()->links() }}</div>
    </div>

    <script>
        const radios = document.querySelectorAll('.cadastro-radio');
        const btnView = document.getElementById('btn-view');
        const btnEdit = document.getElementById('btn-edit');
        const btnDownload = document.getElementById('btn-download');

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const selected = document.querySelector('.cadastro-radio:checked');
                const enable = !!selected;

                if (btnView) btnView.disabled = !enable;
                if (btnEdit) btnEdit.disabled = !enable;
                if (btnDownload) btnDownload.disabled = !enable;
            });
        });

        function viewSelected() {
            const selected = document.querySelector('.cadastro-radio:checked');
            if (selected) {
                window.location.href = '/cadastros/' + selected.value;
            }
        }

        function editSelected() {
            const selected = document.querySelector('.cadastro-radio:checked');
            if (selected) {
                window.location.href = '/cadastros/' + selected.value + '/edit';
            }
        }

        function downloadSelected() {
            const selected = document.querySelector('.cadastro-radio:checked');
            if (selected) {
                window.location.href = '/cadastros/' + selected.value + '/download';
            }
        }

        // Bind click handlers safely
        if (btnView) btnView.addEventListener('click', viewSelected);
        if (btnEdit) btnEdit.addEventListener('click', editSelected);
        if (btnDownload) btnDownload.addEventListener('click', downloadSelected);
    </script>
</body>
</html>