<div class="mt-4">
    <strong>Arquivos:</strong>
    <ul class="mt-2 space-y-1">
        @foreach($arquivos as $path)
            <li>
                @if(isset($filesExist[$path]) && $filesExist[$path] === true)
                    @if(auth()->check())
                        <a href="{{ route('cadastros.arquivo.download', ['uuid' => $uuid, 'path' => base64_encode($path)]) }}" class="text-blue-600 underline" target="_blank">{{ basename($path) }}</a>
                    @else
                        <span class="text-gray-700">{{ basename($path) }}</span>
                    @endif
                @else
                    <span class="text-gray-700">{{ basename($path) }} <span class="text-red-600">(Arquivo não encontrado)</span></span>
                @endif

                @auth
                    <form method="POST" action="{{ route('cadastros.arquivo.delete', ['uuid' => $uuid]) }}" style="display:inline">
                        @csrf
                        <input type="hidden" name="path" value="{{ $path }}">
                        <button class="text-red-600 ml-2" onclick="return confirm('Excluir arquivo?')">✖</button>
                    </form>
                @endauth
            </li>
        @endforeach
    </ul>
</div>
