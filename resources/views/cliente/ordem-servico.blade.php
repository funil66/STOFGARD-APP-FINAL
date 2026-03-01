<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS #{{ $os->numero_os }} — {{ $config?->empresa_nome }}</title>
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
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($config?->logo_cliente_path)
                    <img src="{{ Storage::url($config->logo_cliente_path) }}" class="h-8 w-auto">
                @else
                    <div class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center">
                        <span
                            class="text-white font-bold text-sm">{{ strtoupper(substr($config?->empresa_nome ?? 'S', 0, 1)) }}</span>
                    </div>
                @endif
                <span class="font-semibold text-gray-900 text-sm">{{ $config?->empresa_nome }}</span>
            </div>
            <a href="{{ route('cliente.portal') }}" class="text-xs text-gray-400 hover:text-brand transition-colors">←
                Voltar</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">

        {{-- Status da OS --}}
        @php
            $statusOs = [
                'aguardando' => ['cor' => 'bg-yellow-50 border-yellow-200 text-yellow-800', 'emoji' => '⏳', 'label' => 'Aguardando'],
                'em_andamento' => ['cor' => 'bg-blue-50 border-blue-200 text-blue-800', 'emoji' => '🔧', 'label' => 'Em Andamento'],
                'concluida' => ['cor' => 'bg-green-50 border-green-200 text-green-800', 'emoji' => '✅', 'label' => 'Concluída'],
                'cancelada' => ['cor' => 'bg-red-50 border-red-200 text-red-800', 'emoji' => '❌', 'label' => 'Cancelada'],
                'aguardando_peca' => ['cor' => 'bg-orange-50 border-orange-200 text-orange-800', 'emoji' => '📦', 'label' => 'Aguardando Peça'],
            ];
            $st = $statusOs[$os->status] ?? ['cor' => 'bg-gray-50 border-gray-200 text-gray-600', 'emoji' => '📋', 'label' => ucfirst($os->status)];
        @endphp

        <div class="border rounded-xl p-4 mb-6 {{ $st['cor'] }}">
            <p class="font-semibold text-lg">{{ $st['emoji'] }} Ordem de Serviço {{ $st['label'] }}</p>
            @if($os->previsao_entrega)
                <p class="text-sm mt-1">
                    📅 Previsão de entrega:
                    <strong>{{ \Carbon\Carbon::parse($os->previsao_entrega)->format('d/m/Y') }}</strong>
                </p>
            @endif
        </div>

        {{-- Dados Principais --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">OS #{{ $os->numero_os }}</h1>
                    <p class="text-gray-500 text-sm">Aberta em {{ $os->created_at->format('d/m/Y') }}</p>
                </div>
                @if($os->tipo_servico)
                    <span class="bg-brand/10 text-brand text-xs px-3 py-1 rounded-full font-medium">
                        {{ $os->tipo_servico }}
                    </span>
                @endif
            </div>

            {{-- Descrição do Problema --}}
            @if($os->descricao_problema)
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Problema Relatado</p>
                    <p class="text-gray-800 text-sm">{{ $os->descricao_problema }}</p>
                </div>
            @endif

            {{-- Laudo Técnico (visível ao concluir) --}}
            @if($os->laudo_tecnico && $os->status === 'concluida')
                <div class="bg-green-50 rounded-xl p-4 mb-4">
                    <p class="text-xs font-medium text-green-700 uppercase tracking-wide mb-1">🔍 Laudo Técnico</p>
                    <p class="text-gray-800 text-sm">{{ $os->laudo_tecnico }}</p>
                </div>
            @endif

            {{-- Linha do tempo simplificada --}}
            <div class="border-t border-gray-100 pt-4 mt-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Histórico</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></div>
                        <p class="text-sm text-gray-600">OS Aberta — {{ $os->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($os->status === 'em_andamento' || $os->status === 'concluida')
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600">Em Andamento</p>
                        </div>
                    @endif
                    @if($os->status === 'concluida')
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600">
                                Concluída — {{ $os->updated_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Garantia (se houver) --}}
        @if($os->garantia)
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-6">
                <h2 class="font-semibold text-blue-900 mb-2">🛡️ Garantia do Serviço</h2>
                <p class="text-sm text-blue-800">
                    Válida até
                    <strong>{{ \Carbon\Carbon::parse($os->garantia->validade_ate ?? $os->garantia->created_at)->format('d/m/Y') }}</strong>
                </p>
                @if($os->garantia->descricao)
                    <p class="text-xs text-blue-700 mt-2">{{ $os->garantia->descricao }}</p>
                @endif
            </div>
        @endif

        {{-- Valor --}}
        @if($os->valor_total)
            <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-700">Valor do Serviço</span>
                    <span class="text-2xl font-bold text-gray-900">
                        R$ {{ number_format((float) $os->valor_total, 2, ',', '.') }}
                    </span>
                </div>
                @if(isset($os->financeiro) && $os->financeiro?->status === 'pago')
                    <p class="text-xs text-green-600 text-right mt-1">✅ Pago</p>
                @endif
            </div>
        @endif

        {{-- Empresa --}}
        <div class="text-center py-6 text-gray-400 text-xs">
            <p>{{ $config?->empresa_nome }} · {{ $config?->telefone }}</p>
        </div>

    </main>
</body>

</html>