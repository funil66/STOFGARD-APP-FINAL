<div class="mt-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
        </svg>
        Arquivos Anexados
    </h3>
    
    <ul class="space-y-2">
        @foreach($arquivos as $path)
            <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                <div class="flex items-center min-w-0">
                    <svg class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    
                    @if(isset($filesExist[$path]) && $filesExist[$path] === true)
                        @if(auth()->check())
                            <a href="{{ route('cadastros.arquivo.download', ['uuid' => $uuid, 'path' => base64_encode($path)]) }}" 
                               class="text-blue-600 hover:text-blue-800 hover:underline text-sm font-medium truncate" 
                               target="_blank">
                                {{ basename($path) }}
                            </a>
                        @else
                            <span class="text-gray-700 text-sm truncate">{{ basename($path) }}</span>
                        @endif
                    @else
                        <span class="text-gray-500 text-sm truncate">
                            {{ basename($path) }}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 ml-2">
                                Não encontrado
                            </span>
                        </span>
                    @endif
                </div>

                @auth
                    <form method="POST" action="{{ route('cadastros.arquivo.delete', ['uuid' => $uuid]) }}" class="flex-shrink-0 ml-3">
                        @csrf
                        <input type="hidden" name="path" value="{{ $path }}">
                        <button type="submit" 
                                class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                                onclick="return confirm('Tem certeza que deseja excluir este arquivo?')"
                                title="Excluir arquivo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                @endauth
            </li>
        @endforeach
    </ul>
</div>
