<div class="space-y-6">
    {{-- Link de Voltar --}}
    <div>
        <a href="{{ route('cadastros.index') }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Voltar para listagem
        </a>
    </div>

    {{-- Cabeçalho --}}
    <div class="border-b border-gray-200 pb-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ $model->nome }}</h1>
        <div class="mt-1">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ ucfirst($type) }}
            </span>
        </div>
    </div>

    {{-- Card de Informações --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
            Informações do Cadastro
        </h2>
        
        <div class="space-y-3">
            @if($type === 'parceiro')
                @include('cadastros.partials.fields_parceiro', ['item' => $model])
            @else
                @include('cadastros.partials.fields_cliente', ['item' => $model])
            @endif
        </div>
    </div>

    {{-- Arquivos Anexados --}}
    @if(! empty($model->arquivos))
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                Arquivos Anexados
            </h2>
            @include('cadastros.partials._arquivos', ['arquivos' => $model->arquivos, 'filesExist' => $filesExist, 'uuid' => $model->uuid])
        </div>
    @endif

    {{-- Ações --}}
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="flex flex-wrap gap-3">
            @auth
                @can('update', $model)
                    <a href="{{ route('cadastros.edit', ['uuid' => $model->uuid]) }}" 
                       class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>
                @endcan
                @can('delete', $model)
                    <form method="POST" action="{{ route('cadastros.destroy', ['uuid' => $model->uuid]) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                                onclick="return confirm('Tem certeza que deseja excluir este cadastro?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Excluir
                        </button>
                    </form>
                @endcan
            @endauth
        </div>
    </div>

    {{-- Mensagem de Sucesso --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    @endif
</div>
