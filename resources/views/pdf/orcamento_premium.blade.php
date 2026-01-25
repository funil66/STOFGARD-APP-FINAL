<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <script src='https://cdn.tailwindcss.com'></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
    </style>
</head>
@php
    // Fallback de segurança: Se $config não vier do controller, busca agora.
    if (!isset($config) || !$config) {
        $config = \App\Models\Configuracao::first() ?? new \App\Models\Configuracao();
    }

    // Garante arrays para evitar erro de acesso a null
    $cores = $config->cores_pdf ?? ['primaria' => '#1e3a8a', 'secundaria' => '#475569'];
    $corPrimaria = $cores['primaria'] ?? '#1e3a8a';
@endphp
<body class='bg-white text-slate-800 antialiased'>
    <div class='fixed left-0 top-0 bottom-0 w-2 bg-[{{ $corPrimaria }}]'></div>
    <div class='max-w-5xl mx-auto p-12'>
        <div class='flex justify-between items-start mb-12'>
            <div>
                @if(optional($config)->empresa_logo)
                    <img src='{{ public_path('storage/' . $config->empresa_logo) }}' class='h-20 object-contain mb-4'>
                @else
                    <h1 class='text-4xl font-bold tracking-tight text-slate-900 uppercase'>{{ optional($config)->empresa_nome }}</h1>
                @endif
                <div class='text-sm text-slate-500 mt-2 space-y-1'>
                    <p>{{ optional($config)->empresa_endereco }}</p>
                    <p>CNPJ: {{ optional($config)->empresa_cnpj }}</p>
                    <p>{{ optional($config)->empresa_telefone }} | {{ optional($config)->empresa_email }}</p>
                </div>
            </div>
            <div class='text-right'>
                <div class='inline-block bg-slate-100 rounded-lg p-4 text-center border border-slate-200'>
                    <div class='text-xs font-semibold text-slate-500 uppercase tracking-wider'>Orçamento Nº</div>
                    <div class='text-3xl font-bold text-[{{ $corPrimaria }}]'>#{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div class='text-xs text-slate-400 mt-1'>{{ $record->created_at->format('d/m/Y') }}</div>
                </div>
                <div class='mt-4 text-sm'>
                    <p class='font-semibold text-slate-700'>Válido até:</p>
                    <p class='text-slate-600'>{{ \Carbon\Carbon::parse($record->validade)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
        <div class='bg-slate-50 rounded-xl p-6 border border-slate-100 mb-10 flex justify-between items-center'>
            <div>
                <div class='text-xs font-bold text-slate-400 uppercase tracking-wider mb-1'>Cliente</div>
                <h2 class='text-xl font-bold text-slate-800'>{{ $record->cliente->nome }}</h2>
            </div>
            <div class='text-right text-sm text-slate-600'>
                <p>{{ $record->cliente->endereco }}, {{ $record->cliente->bairro }}</p>
                <p>{{ $record->cliente->cidade }}</p>
                <p class='font-medium text-slate-800 mt-1'>{{ $record->cliente->telefone }}</p>
            </div>
        </div>
        <div class='mb-12'>
            <h3 class='text-lg font-bold text-slate-800 mb-4 border-b pb-2'>Descrição dos Serviços</h3>
            <table class='w-full'>
                <thead>
                    <tr class='text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200'>
                        <th class='pb-3'>Item</th>
                        <th class='pb-3 text-center'>Qtd</th>
                        <th class='pb-3 text-right'>Unitário</th>
                        <th class='pb-3 text-right'>Total</th>
                    </tr>
                </thead>
                <tbody class='text-sm divide-y divide-slate-100'>
                    @foreach($record->itens as $item)
                    <tr>
                        <td class='py-4'>
                            <p class='font-semibold text-slate-700'>{{ $item['item'] }}</p>
                            @if(isset($item['descricao']) && $item['descricao'])
                                <p class='text-xs text-slate-400 mt-0.5'>{{ $item['descricao'] }}</p>
                            @endif
                        </td>
                        <td class='py-4 text-center text-slate-600'>{{ $item['quantidade'] }}</td>
                        <td class='py-4 text-right text-slate-600'>R$ {{ number_format($item['valor_unitario'], 2, ',', '.') }}</td>
                        <td class='py-4 text-right font-bold text-slate-800'>R$ {{ number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @php
            $total = $record->valor_total;
            $descontoPix = $config->desconto_pix ?? 10;
            $valorPix = $total * (1 - ($descontoPix / 100));
            $taxas = $config->taxas_parcelamento ?? [];
            $repassar = $record->repassar_taxas;
        @endphp
        <div class='grid grid-cols-12 gap-8 mb-12'>
            <div class='col-span-7'>
                @if($record->observacoes)
                    <div class='bg-yellow-50 border border-yellow-100 rounded-lg p-4'>
                        <h4 class='text-xs font-bold text-yellow-700 uppercase mb-2'>Observações</h4>
                        <p class='text-sm text-yellow-800 italic'>{{ $record->observacoes }}</p>
                    </div>
                @endif
                <div class='mt-6 text-xs text-slate-400 text-justify'>
                    <strong>Garantia e Termos:</strong><br>
                    {!! $config->termos_garantia !!}
                </div>
            </div>
            <div class='col-span-5'>
                <div class='bg-slate-900 rounded-xl p-6 text-white shadow-lg'>
                    <div class='flex justify-between items-center mb-6'>
                        <span class='text-slate-400 uppercase text-xs font-bold tracking-wider'>Valor Total</span>
                        <span class='text-3xl font-bold'>R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                    <div class='mb-6 bg-slate-800/50 rounded-lg p-4 border border-slate-700'>
                        <div class='flex justify-between items-center mb-1'>
                            <span class='text-green-400 font-bold text-sm'>À VISTA (PIX)</span>
                            <span class='bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded'>-{{ $descontoPix }}% OFF</span>
                        </div>
                        <div class='text-2xl font-bold text-green-400'>R$ {{ number_format($valorPix, 2, ',', '.') }}</div>
                        <div class='text-xs text-slate-400 mt-1'>Economia de R$ {{ number_format($total - $valorPix, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class='text-xs font-bold text-slate-500 uppercase tracking-wider mb-2'>Cartão de Crédito</div>
                        <div class='space-y-2 text-sm'>
                            <div class='flex justify-between'>
                                <span class='text-slate-300'>1x (Sem Juros)</span>
                                <span class='font-medium'>R$ {{ number_format($total, 2, ',', '.') }}</span>
                            </div>
                            @foreach($taxas as $qtd => $fator)
                                @if($qtd <= 6)
                                    @php
                                        $totalP = $repassar ? $total * $fator : $total;
                                        $parc = $totalP / $qtd;
                                    @endphp
                                    <div class='flex justify-between border-t border-slate-800 pt-1'>
                                        <span class='text-slate-400'>{{ $qtd }}x de R$ {{ number_format($parc, 2, ',', '.') }}</span>
                                        <span class='text-slate-500'>Total: R$ {{ number_format($totalP, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @if(!$repassar)
                            <div class='mt-3 text-center text-xs font-bold text-blue-400'>
                                ★ PARCELAMENTO SEM JUROS ★
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class='mt-20 flex justify-between items-end'>
            <div class='w-1/3 border-t border-slate-300 pt-2 text-center'>
                <p class='text-xs font-bold text-slate-500 uppercase'>{{ $config->empresa_nome }}</p>
            </div>
            <div class='w-1/3 border-t border-slate-300 pt-2 text-center'>
                <p class='text-xs font-bold text-slate-500 uppercase'>Cliente: {{ $record->cliente->nome }}</p>
                <p class='text-[10px] text-slate-400'>Li e concordo com os termos.</p>
            </div>
        </div>
    </div>
</body>
</html>
