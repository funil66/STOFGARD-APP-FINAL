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

        {{-- Alertas de Ação --}}
        @if(isset($alertasAcao) && $alertasAcao->count() > 0)
            <div class="mb-8 space-y-3">
                @foreach($alertasAcao as $alerta)
                    <a href="{{ $alerta['link'] }}" class="block bg-{{ $alerta['cor'] }}-50 border border-{{ $alerta['cor'] }}-200 rounded-xl p-4 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute inset-y-0 left-0 w-1 bg-{{ $alerta['cor'] }}-400"></div>
                        <div class="flex items-start gap-3 pl-2">
                            <div class="mt-0.5 text-{{ $alerta['cor'] }}-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-{{ $alerta['cor'] }}-900 text-sm">Ação Necessária: {{ $alerta['tipo'] }} #{{ $alerta['numero'] }}</h3>
                                <p class="text-{{ $alerta['cor'] }}-800 text-sm mt-0.5">{{ $alerta['mensagem'] }}</p>
                            </div>
                            <div class="text-{{ $alerta['cor'] }}-600 font-semibold text-sm group-hover:underline">
                                Assinar Agora &rarr;
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Faturas e Histórico Financeiro --}}
        @if($faturas->count() > 0)
            <section class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">💸 Minhas Faturas</h2>
                <div class="space-y-3">
                    @foreach($faturas as $fatura)
                        <div class="block bg-white rounded-xl border {{ $fatura->status === 'pendente' || $fatura->status === 'atrasado' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-100' }} p-4 hover:shadow-sm transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $fatura->descricao }}</p>
                                    <p class="text-sm text-gray-500">
                                        Vencimento: <span class="{{ $fatura->status === 'atrasado' ? 'text-red-500 font-semibold' : '' }}">{{ $fatura->data_vencimento->format('d/m/Y') }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">R$ {{ number_format($fatura->valor, 2, ',', '.') }}</p>
                                    <div class="mt-1 flex justify-end gap-2">
                                        <span class="inline-block text-xs px-2 py-0.5 rounded-full
                                            @if($fatura->status === 'pago') bg-green-100 text-green-700
                                            @elseif($fatura->status === 'pendente') bg-yellow-200 text-yellow-800
                                            @elseif($fatura->status === 'atrasado') bg-red-100 text-red-700
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ ucfirst($fatura->status) }}
                                        </span>
                                        @if($fatura->status === 'pendente' || $fatura->status === 'atrasado')
                                            <!-- Pix Link / Botão genérico caso o sistema gere link no futuro -->
                                            <button onclick="alert('Funcionalidade de pagamento online será ativada em breve. Por favor, contate o atendimento para o link PIX.')" class="text-xs bg-brand hover:opacity-90 text-white px-3 py-1 rounded-full font-medium transition-colors">
                                                Pagar Agora
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

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

        {{-- Warranties (Garantias) --}}
        @if(isset($garantias) && $garantias->count() > 0)
            <section class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🛡️ Meus Certificados de Garantia</h2>
                <div class="space-y-3">
                    @foreach($garantias as $garantia)
                        @php
                            $isVencida = now()->startOfDay()->gt(\Carbon\Carbon::parse($garantia->data_fim));
                        @endphp
                        <div class="block bg-white rounded-xl border {{ $isVencida ? 'border-gray-100 bg-gray-50' : 'border-green-200 bg-green-50' }} p-4 transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">OS #{{ $garantia->ordemServico->numero_os ?? 'N/A' }} - {{ $garantia->condicoes_garantia ?? 'Garantia de Serviço' }}</p>
                                    <p class="text-sm text-gray-500">
                                        Validade: <span class="{{ $isVencida ? 'text-red-500' : 'text-green-600 font-medium' }}">{{ \Carbon\Carbon::parse($garantia->data_fim)->format('d/m/Y') }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $isVencida ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                        {{ $isVencida ? 'Vencida' : 'Ativa' }}
                                    </span>
                                    @if($garantia->ordem_servico_id)
                                    <div class="mt-2">
                                        <a href="{{ route('cliente.os', $garantia->ordem_servico_id) }}" class="text-xs text-brand hover:underline">Ver Detalhes da OS</a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
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

        @if($orcamentos->isEmpty() && $ordensServico->isEmpty() && $faturas->isEmpty() && (!isset($garantias) || $garantias->isEmpty()))
            <div class="text-center py-16 text-gray-400">
                <p class="text-4xl mb-3">📭</p>
                <p class="font-medium">Nenhum serviço encontrado ainda.</p>
            </div>
        @endif

    </main>
</body>

</html>