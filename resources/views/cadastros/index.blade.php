<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gestão de Cadastros - StofGard">
    <title>Cadastros | StofGard</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto p-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Cadastros</h1>
            <p class="text-sm text-gray-500 mt-1">Gerencie clientes, lojas e vendedores</p>
        </div>

        {{-- Tabs de Filtro --}}
        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('cadastros.index') }}" 
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cadastros.index') && !request()->routeIs('cadastros.lojas') && !request()->routeIs('cadastros.vendedores') ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                Todos
            </a>
            <a href="{{ route('cadastros.lojas') }}" 
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cadastros.lojas') ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                Lojas
            </a>
            <a href="{{ route('cadastros.vendedores') }}" 
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cadastros.vendedores') ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                Vendedores
            </a>
        </div>

        {{-- Barra de Pesquisa --}}
        <form method="GET" action="{{ route('cadastros.index') }}" class="mb-6">
            <div class="flex gap-2">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ request('q') }}" 
                        placeholder="Pesquisar por nome, email ou telefone..." 
                        class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    >
                </div>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Buscar
                </button>
            </div>
        </form>

        {{-- Lista de Cadastros --}}
        <form id="cadastro-form">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                @forelse($cadastros as $c)
                    @php
                        $under = $c->underlying_model ?? null;
                        $linkId = $under?->uuid ?? ($under?->id ?? null);
                    @endphp

                    <div class="p-4 hover:bg-gray-50 flex justify-between items-center border-b border-gray-100 last:border-b-0 transition-colors">
                        <div class="flex items-center min-w-0">
                            @auth
                                <input type="radio" name="selected" value="{{ $linkId }}" class="mr-4 cadastro-radio h-4 w-4 text-blue-600 focus:ring-blue-500">
                            @endauth
                            <div class="min-w-0">
                                @if(! empty($linkId))
                                    <a href="{{ route('cadastros.show', ['uuid' => $linkId]) }}" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline truncate block">
                                        {{ $c->nome }}
                                    </a>
                                @else
                                    <div class="font-semibold text-gray-800 truncate">{{ $c->nome }}</div>
                                @endif
                                <div class="text-sm text-gray-500 truncate">{{ $c->email ?? ($c->celular ?? '—') }}</div>
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $c->tipo === 'loja' ? 'bg-purple-100 text-purple-800' : 
                                   ($c->tipo === 'vendedor' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ ucfirst($c->tipo) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum cadastro encontrado</h3>
                        <p class="mt-1 text-sm text-gray-500">Tente ajustar os filtros ou termos de busca.</p>
                    </div>
                @endforelse
            </div>

            {{-- Botões de Ação --}}
            @auth
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" id="btn-view" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Visualizar
                    </button>

                    @if(auth()->user()->email === 'allisson@stofgard.com.br')
                        <button type="button" id="btn-edit" class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar
                        </button>
                    @endif

                    <button type="button" id="btn-download" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download
                    </button>
                </div>
            @endauth
        </form>

        {{-- Paginação --}}
        <div class="mt-6">{{ $cadastros->withQueryString()->links() }}</div>
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