<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Contrato de Prestação de Serviço - #{{ $orcamento->numero }}</title>
    <style>
        @page {
            margin: 0;
        }

        @php
            $primary = data_get($config, 'pdf_color_primary', '#2563eb');
            $text = data_get($config, 'pdf_color_text', '#1f2937');
        @endphp

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: {{ $text }};
            line-height: 1.6;
            margin: 0;
            padding: 4.5cm 1cm 2.6cm 1cm;
            font-size: 14px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 1cm;
            right: 1cm;
            height: 3.8cm;
            padding-top: 0.5cm;
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: white;
            z-index: 1000;
        }

        .logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 8px;
        }

        .company-info {
            font-size: 8.5px;
            color: #374151;
            line-height: 1.6;
        }

        .header-right {
            background: {{ $primary }};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: right;
            min-width: 190px;
        }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .doc-ref {
            font-size: 9px;
            line-height: 1.6;
        }

        h2 {
            font-size: 16px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 30px;
        }

        .content {
            text-align: justify;
        }

        .signatures {
            margin-top: 60px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
            margin-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 40px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            background: white;
            font-size: 8px;
            text-align: center;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 6px;
            height: 1.8cm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f9fafb;
        }
    </style>
</head>

<body>

    <div class="header">
        <div style="max-width: 55%;">
            @if(!empty($config->empresa_logo) && file_exists(public_path('storage/' . $config->empresa_logo)))
                <img src="{{ public_path('storage/' . $config->empresa_logo) }}" class="logo" alt="Logo">
            @else
                <div style="font-size: 16px; font-weight: bold; color: {{ $primary }}; margin-bottom: 8px;">
                    {{ $config->empresa_nome ?? 'Empresa' }}
                </div>
            @endif
            <div class="company-info">
                {{ $config->empresa_nome ?? '' }}<br>
                CNPJ: {{ $config->empresa_cnpj ?? '' }}<br>
                {{ $config->empresa_telefone ?? '' }}
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">CONTRATO</div>
            <div class="doc-ref">
                <strong>Ref. Orçamento:</strong> #{{ $orcamento->numero }}<br>
                Emissão: {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    @php
        // Extrai os itens do orçamento formatados para a variável {itens}
        $itensHtml = '<table><thead><tr><th>Item/Serviço</th><th>Qtd</th><th>Valor Unit.</th><th>Subtotal</th></tr></thead><tbody>';
        foreach ($orcamento->itens ?? [] as $item) {
            $itensHtml .= '<tr>';
            $itensHtml .= '<td>' . ($item['item_nome'] ?? 'Serviço') . '</td>';
            $itensHtml .= '<td>' . ($item['quantidade'] ?? '1') . '</td>';
            $itensHtml .= '<td>R$ ' . number_format($item['valor_unitario'] ?? 0, 2, ',', '.') . '</td>';
            $itensHtml .= '<td>R$ ' . number_format($item['subtotal'] ?? 0, 2, ',', '.') . '</td>';
            $itensHtml .= '</tr>';
        }
        $itensHtml .= '</tbody></table>';

        $textoBase = $config->texto_contrato_padrao ?? 'Declaro para os devidos fins que o CONTRATANTE({cliente_nome}) autoriza a CONTRATADA a realizar os serviços ({itens}) pelo valor acordado de R$ {valor_total}.';

        // Substituições
        $textoFinal = str_replace(
            ['{cliente_nome}', '{cliente_doc}', '{valor_total}', '{itens}'],
            [
                $orcamento->cliente->nome ?? 'N/A',
                $orcamento->cliente->documento ?? 'N/A',
                number_format($orcamento->valor_efetivo ?? $orcamento->valor_total, 2, ',', '.'),
                $itensHtml
            ],
            $textoBase
        );
    @endphp

    <div class="content">
        <h2>1. IDENTIFICAÇÃO DAS PARTES</h2>
        <p><strong>CONTRATANTE:</strong> {{ $orcamento->cliente->nome ?? 'N/A' }}, inscrito(a) sob o CPF/CNPJ
            {{ $orcamento->cliente->documento ?? 'N/A' }}, com endereço em
            {{ $orcamento->cliente->logradouro ?? 'N/A' }}, {{ $orcamento->cliente->numero ?? 'S/N' }} -
            {{ $orcamento->cliente->cidade ?? 'N/A' }}/{{ $orcamento->cliente->estado ?? 'N/A' }}.</p>
        <p><strong>CONTRATADA:</strong> {{ $config->empresa_nome ?? 'Empresa Teste' }}, inscrita sob o CNPJ/CPF
            {{ $config->empresa_cnpj ?? '00.000.000/0001-00' }}.</p>

        <h2>2. OBJETO DO CONTRATO E VALORES</h2>
        {!! $textoFinal !!}

        @if(!empty($config->termos_garantia))
            <h2>3. TERMOS DE GARANTIA E CONDIÇÕES GERAIS</h2>
            {!! $config->termos_garantia !!}
        @endif
    </div>

    <div class="signatures">
        <div class="signature-box" style="float: left;">
            <div class="signature-line">
                <strong>CONTRATADA</strong><br>
                {{ $config->empresa_nome ?? 'Empresa' }}<br>
                {{ $config->empresa_cnpj ?? '' }}
            </div>
        </div>
        <div class="signature-box" style="float: right;">
            <div class="signature-line">
                <strong>CONTRATANTE (CLIENTE)</strong><br>
                {{ $orcamento->cliente->nome ?? 'Cliente' }}<br>
                CPF/CNPJ: {{ $orcamento->cliente->documento ?? '' }}
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        Gerado por Autonomia Ilimitada em {{ now()->format('d/m/Y H:i') }} - IP: {{ request()->ip() }}
    </div>

</body>

</html>