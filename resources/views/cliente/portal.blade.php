<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Cliente — {{ $config?->empresa_nome ?? 'Autonomia Ilimitada' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --cor-brand:
                {{ $config?->cor_primaria_cliente ?? '#6366f1' }}
            ;
        }

        .bg-brand {
            background-color: var(--cor-brand);
        }

        .text-brand {
            color: var(--cor-brand);
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-100 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($config?->logo_cliente_path)
                    <img src="{{ Storage::url($config->logo_cliente_path) }}" class="h-8 w-auto">
                @else
                    <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center">
                        <span
                            class="text-white font-bold text-sm">{{ strtoupper(substr($config?->empresa_nome ?? 'S', 0, 1)) }}</span>
                    </div>
                @endif
                <span class="font-semibold text-gray-900 text-sm">{{ $config?->empresa_nome ?? 'Autonomia Ilimitada' }}</span>
            </div>
            <form action="{{ route('cliente.logout') }}" method="POST">
                @csrf
                <button class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Sair →</button>
            </form>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Meu Portal 👋</h1>
            <p class="text-gray-500 text-sm mt-1">Acompanhe seus serviços, orçamentos e documentos</p>
        </div>

        {{-- Orçamentos --}}
        @if($orcamentos->count() > 0)
            <section class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Meus Orçamentos</h2>
                <div class="space-y-3">
                    @foreach($orcamentos as $orc)
                        <a href="{{ route('cliente.orcamento', $orc->id) }}"
                            class="block bg-white rounded-xl border border-gray-100 p-4 hover:border-brand hover:shadow-sm transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">#{{ $orc->numero_orcamento }}</p>
                                    <p class="text-sm text-gray-500">{{ $orc->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">R$
                                        {{ number_format($orc->valor_total, 2, ',', '.') }}</p>
                                    <span
                                        class="inline-block text-xs px-2 py-0.5 rounded-full
                                        {{ $orc->status === 'aprovado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ ucfirst($orc->status) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Ordens de Serviço --}}
        @if($ordensServico->count() > 0)
            <section class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🔧 Minhas Ordens de Serviço</h2>
                <div class="space-y-3">
                    @foreach($ordensServico as $os)
                        <a href="{{ route('cliente.os', $os->id) }}"
                            class="block bg-white rounded-xl border border-gray-100 p-4 hover:border-brand hover:shadow-sm transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">OS #{{ $os->numero_os }}</p>
                                    <p class="text-sm text-gray-500">{{ $os->created_at->format('d/m/Y') }}</p>
                                </div>
                                <span
                                    class="inline-block text-xs px-2 py-0.5 rounded-full
                                    {{ $os->status === 'concluida' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $os->status)) }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($orcamentos->isEmpty() && $ordensServico->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <p class="text-4xl mb-3">📭</p>
                <p class="font-medium">Nenhum serviço encontrado ainda.</p>
            </div>
        @endif

    </main>
</body>

</html>