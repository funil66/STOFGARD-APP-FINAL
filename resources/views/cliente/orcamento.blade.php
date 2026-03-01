<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #{{ $orcamento->numero_orcamento }} — {{ $config?->empresa_nome }}</title>
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

        .border-brand {
            border-color: var(--cor-brand);
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

        {{-- Status Banner --}}
        @php
            $statusColor = match ($orcamento->status) {
                'aprovado' => 'bg-green-50 border-green-200 text-green-800',
                'pendente' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'reprovado' => 'bg-red-50 border-red-200 text-red-800',
                'expirado' => 'bg-gray-50 border-gray-200 text-gray-600',
                default => 'bg-blue-50 border-blue-200 text-blue-800',
            };
            $statusEmoji = match ($orcamento->status) {
                'aprovado' => '✅',
                'pendente' => '⏳',
                'reprovado' => '❌',
                'expirado' => '⌛',
                default => '📋',
            };
        @endphp
        <div class="border rounded-xl p-4 mb-6 {{ $statusColor }}">
            <p class="font-semibold text-lg">{{ $statusEmoji }} Orçamento {{ ucfirst($orcamento->status) }}</p>
            <p class="text-sm mt-0.5">{{ $orcamento->created_at->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}
            </p>
        </div>

        {{-- Dados do Orçamento --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h1 class="text-xl font-bold text-gray-900 mb-1">
                Orçamento #{{ $orcamento->numero_orcamento }}
            </h1>
            <p class="text-gray-500 text-sm mb-6">
                Emitido em {{ $orcamento->created_at->format('d/m/Y') }}
                @if($orcamento->validade)
                    · Válido até {{ \Carbon\Carbon::parse($orcamento->validade)->format('d/m/Y') }}
                @endif
            </p>

            {{-- Itens / Serviços --}}
            @if($orcamento->itens && $orcamento->itens->count() > 0)
                <table class="w-full text-sm mb-6">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-gray-500">
                            <th class="pb-3 font-medium">Serviço</th>
                            <th class="pb-3 font-medium text-right">Qtd</th>
                            <th class="pb-3 font-medium text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orcamento->itens as $item)
                            <tr class="border-b border-gray-50">
                                <td class="py-3 text-gray-800">{{ $item->nome_item ?? $item->descricao ?? '-' }}</td>
                                <td class="py-3 text-right text-gray-600">{{ $item->quantidade ?? 1 }}</td>
                                <td class="py-3 text-right font-medium text-gray-900">
                                    R$
                                    {{ number_format((float) ($item->valor_total ?? $item->valor_unitario ?? 0), 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Observações --}}
            @if($orcamento->observacoes)
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <p class="text-sm font-medium text-gray-700 mb-1">Observações</p>
                    <p class="text-sm text-gray-600">{{ $orcamento->observacoes }}</p>
                </div>
            @endif

            {{-- Total --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <span class="text-gray-700 font-semibold">Total</span>
                <span class="text-2xl font-bold text-gray-900">
                    R$ {{ number_format((float) $orcamento->valor_total, 2, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- PIX de pagamento (se aprovado e aguardando pagamento) --}}
        @if($orcamento->pix_copia_cola && $orcamento->status_pagamento !== 'pago')
            <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-6">
                <h2 class="font-semibold text-green-900 mb-4">💚 Pague via PIX</h2>
                <div class="bg-white rounded-xl p-4 break-all text-xs text-gray-600 font-mono border border-green-100 mb-3">
                    {{ $orcamento->pix_copia_cola }}
                </div>
                <button
                    onclick="navigator.clipboard.writeText('{{ $orcamento->pix_copia_cola }}').then(() => { this.textContent = '✅ Copiado!'; setTimeout(() => this.textContent = '📋 Copiar PIX Copia e Cola', 2000); })"
                    class="w-full bg-green-600 text-white py-3 px-4 rounded-xl font-medium text-sm hover:bg-green-700 transition-all">
                    📋 Copiar PIX Copia e Cola
                </button>
                <p class="text-xs text-green-700 text-center mt-2">
                    Valor: R$ {{ number_format((float) $orcamento->valor_total, 2, ',', '.') }}
                </p>
            </div>
        @endif

        {{-- Empresa --}}
        <div class="text-center py-6 text-gray-400 text-xs">
            <p>{{ $config?->empresa_nome }} · {{ $config?->telefone }}</p>
            @if($config?->email)
                <p>{{ $config->email }}</p>
            @endif
        </div>

    </main>
</body>

</html>