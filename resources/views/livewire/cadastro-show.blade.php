<div>
    <a href="{{ route('cadastros.index') }}" class="text-sm text-blue-600 underline">&larr; Voltar</a>

    <h1 class="text-2xl font-bold mt-4">{{ $model->nome }}</h1>
    <div class="text-sm text-gray-600 mb-4">Tipo: {{ ucfirst($type) }}</div>

    <div class="bg-white p-4 rounded shadow">
        @if($type === 'parceiro')
            @include('cadastros.partials.fields_parceiro', ['item' => $model])
        @else
            @include('cadastros.partials.fields_cliente', ['item' => $model])
        @endif

        @if(! empty($model->arquivos))
            @include('cadastros.partials._arquivos', ['arquivos' => $model->arquivos, 'filesExist' => $filesExist, 'uuid' => $model->uuid])
        @endif

        <div class="mt-4 space-x-2">
            @auth
                @can('update', $model)
                    <a href="{{ route('cadastros.edit', ['uuid' => $model->uuid]) }}" class="px-3 py-2 bg-yellow-500 text-white rounded">Editar</a>
                @endcan
                @can('delete', $model)
                    <form method="POST" action="{{ route('cadastros.destroy', ['uuid' => $model->uuid]) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-2 bg-red-600 text-white rounded" onclick="return confirm('Excluir cadastro?')">Excluir</button>
                    </form>
                @endcan
            @endauth
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 text-green-600">{{ session('success') }}</div>
    @endif
</div>
