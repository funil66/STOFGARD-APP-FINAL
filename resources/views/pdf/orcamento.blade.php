<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $record->numero_orcamento }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f4f4f4; font-weight: bold; }
        .highlight { font-size: 16px; font-weight: bold; color: #1d4ed8; }
        .success { color: #16a34a; font-weight: bold; }
        .note { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $config = \App\Models\Configuracao::first();
        $taxas = $config->taxas_parcelamento ?? [];
        $descontoPix = $config->desconto_pix ?? 10;
        $valorTotal = $record->valor_total;
        $aplicarPix = $record->aplicar_desconto_pix;
        $repassarTaxas = $record->repassar_taxas;
        $valorPix = $valorTotal * (1 - ($descontoPix / 100));
    @endphp

    <h1>Resumo Financeiro</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Forma de Pagamento</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @if($aplicarPix)
                <tr>
                    <td class="success">À Vista (Pix/Dinheiro)</td>
                    <td class="success">R$ {{ number_format($valorPix, 2, ',', '.') }} (-{{ $descontoPix }}%)</td>
                </tr>
            @endif
            <tr>
                <td>Crédito 1x / Boleto</td>
                <td>R$ {{ number_format($valorTotal, 2, ',', '.') }}</td>
            </tr>
            @for($i = 2; $i <= 6; $i++)
                @php
                    $coeficiente = $taxas[$i] ?? 1;
                    $totalParcelado = $repassarTaxas ? ($valorTotal * $coeficiente) : $valorTotal;
                    $valorParcela = $totalParcelado / $i;
                @endphp
                <tr>
                    <td>{{ $i }}x @if(!$repassarTaxas)<span class="note">(Sem Juros)</span>@endif</td>
                    <td>R$ {{ number_format($valorParcela, 2, ',', '.') }} <span class="note">(Total: R$ {{ number_format($totalParcelado, 2, ',', '.') }})</span></td>
                </tr>
            @endfor
        </tbody>
    </table>

    @if(!$repassarTaxas)
        <p class="note">TAXAS ISENTAS (CORTESIA STOFGARD)</p>
    @endif
</body>
</html>
