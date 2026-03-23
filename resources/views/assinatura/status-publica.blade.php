<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status da Assinatura - AUTONOMIA ILIMITADA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Status da Assinatura</h1>
            <p class="text-gray-600 text-sm">Acompanhe o estado atual da contratação da sua empresa.</p>
        </div>

        @php
            $status = strtoupper($tenant->status_pagamento ?? 'N/A');
            $statusClass = match (strtolower($tenant->status_pagamento ?? '')) {
                'active', 'ativo', 'paid', 'pago' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'trial' => 'bg-blue-100 text-blue-700 border-blue-200',
                'pending', 'pendente' => 'bg-amber-100 text-amber-700 border-amber-200',
                default => 'bg-slate-100 text-slate-700 border-slate-200',
            };
        @endphp

        <div class="mb-5 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $statusClass }}">
                {{ $status }}
            </span>
        </div>

        <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-2 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div class="font-medium text-gray-500">Empresa:</div>
                <div>{{ $tenant->name }}</div>

                <div class="font-medium text-gray-500">Plano:</div>
                <div>{{ strtoupper($tenant->plan ?? 'N/A') }}</div>

                <div class="font-medium text-gray-500">Status:</div>
                <div>{{ strtoupper($tenant->status_pagamento ?? 'N/A') }}</div>

                <div class="font-medium text-gray-500">Vencimento:</div>
                <div>{{ optional($tenant->data_vencimento)->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="mt-6 text-xs text-gray-500 text-center">
            Se o pagamento já foi feito, o status pode levar alguns instantes para refletir.
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('empresa.login') }}" class="text-sm text-blue-600 hover:text-blue-700 underline">Ir para login da empresa</a>
        </div>
    </div>
</body>
</html>
