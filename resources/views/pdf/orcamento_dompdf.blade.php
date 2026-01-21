<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orçamento STOFGARD</title>
    <style>
        /* --- CONFIGURAÇÃO GERAL E TIPOGRAFIA --- */
        @page {
            margin: 2cm; /* DomPDF default margins, adjust if content overflows */
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
            margin: 0;
        }
        
        /* --- UTILITÁRIOS --- */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .w-100 { width: 100%; }
        .mb-10 { margin-bottom: 10px; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
        .gray { color: #666; }
        .primary-color { color: #004aad; } /* Azul Stofgard Estimado */

        /* --- CABEÇALHO --- */
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 180px; /* Ajuste conforme a resolução da sua imagem */
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #004aad;
            letter-spacing: 1px;
            margin: 0;
        }
        .company-details {
            font-size: 9pt;
            color: #555;
        }

        /* --- METADADOS (ID e DATA) --- */
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #004aad;
            padding-bottom: 10px;
        }
        .meta-title {
            font-size: 12pt;
            font-weight: bold;
            color: #004aad;
        }

        /* --- DADOS DO CLIENTE --- */
        .section-title {
            background-color: #eee;
            color: #333;
            font-weight: bold;
            padding: 5px 10px;
            border-left: 5px solid #004aad;
            margin-bottom: 10px;
            font-size: 10pt;
        }
        .client-info td {
            padding: 2px 0;
        }

        /* --- TABELA DE ITENS --- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #004aad;
            color: #fff;
            padding: 8px;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        .category-header {
            background-color: #f9f9f9;
            font-weight: bold;
            padding: 10px !important;
            border-bottom: 1px solid #ccc;
        }
        .category-desc {
            display: block;
            font-weight: normal;
            font-size: 8pt;
            color: #666;
            margin-top: 4px;
            font-style: italic;
        }

        /* --- TOTAIS --- */
        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 5px;
            text-align: right;
        }
        .total-final {
            font-size: 14pt;
            font-weight: bold;
            color: #004aad;
            border-top: 2px solid #004aad;
            padding-top: 10px !important;
        }

        /* --- PIX --- */
        .pix-container {
            clear: both; /* Limpa o float dos totais */
            margin-top: 140px; /* Empurra para baixo para não colidir com float */
            border: 1px dashed #ccc;
            padding: 15px;
            background-color: #fcfcfc;
        }
        .pix-code {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            background: #eee;
            padding: 8px;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #ddd;
        }

        /* --- RODAPÉ --- */
        .footer {
            /* Removed position: fixed */
            font-size: 8pt;
            text-align: center;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
            margin-top: 30px; /* Add some space from the content above */
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path("images/logo-stofgard.png") }}" alt="Logo Stofgard" class="logo">
        <h1 class="company-name">STOFGARD</h1>
        <div class="company-details">Higienização & Impermeabilização</div>
        <div class="company-details">
            CNPJ: 58.794.846/0001-20 | Telefone: (16) 99104-0195<br>
            E-mail: contato@stofgard.com.br
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%" valign="bottom">
                <div class="section-title" style="margin:0; width: 150px;">DADOS DO CLIENTE</div>
            </td>
            <td width="50%" class="text-right" valign="bottom">
                <span class="meta-title">{{ $orcamento->codigo ?? 'ORC20260011' }}</span><br>
                <span style="font-size: 9pt;">
                    Emissão: {{ $orcamento->data_emissao->format('d/m/Y') ?? '11/01/2026' }}<br>
                    <strong>Válido até: {{ $orcamento->data_validade->format('d/m/Y') ?? '18/01/2026' }}</strong>
                </span>
            </td>
        </tr>
    </table>

    <div class="mb-20">
        <table class="w-100 client-info">
            <tr>
                <td width="15%" class="bold">Cliente:</td>
                <td>{{ $orcamento->cliente->nome ?? 'ALLISSON G SOUSA' }}</td>
            </tr>
            <tr>
                <td class="bold">E-mail:</td>
                <td>{{ $orcamento->cliente->email ?? 'allissonsousa.adv@gmail.com' }}</td>
            </tr>
             @if ($orcamento->cliente->telefone)
                <tr>
                    <td class="bold">Telefone:</td>
                    <td>{{ $orcamento->cliente->telefone }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section-title">ITENS DO ORÇAMENTO</div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" width="45%">Item / Serviço</th>
                <th class="text-center" width="10%">UN</th>
                <th class="text-center" width="10%">Qtd</th>
                <th class="text-right" width="15%">Valor Un.</th>
                <th class="text-right" width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $groupedItems = $orcamento->itens->groupBy('categoria_servico');
            @endphp

            @foreach ($groupedItems as $categoria => $itemsOfCategory)
                <tr>
                    <td colspan="5" class="category-header">
                        {{ $categoria }}
                        @if (isset($itemsOfCategory->first()->descricao_categoria))
                            <span class="category-desc">
                                {{ $itemsOfCategory->first()->descricao_categoria }}
                            </span>
                        @endif
                    </td>
                </tr>
                @foreach ($itemsOfCategory as $item)
                    <tr>
                        <td>{{ $item->descricao }}</td>
                        <td class="text-center">{{ $item->unidade ?? 'UN' }}</td>
                        <td class="text-center">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_total_item, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="bold">Subtotal:</td>
            <td>R$ {{ number_format($orcamento->valor_total_itens, 2, ',', '.') }}</td>
        </tr>
        @if ($orcamento->desconto > 0)
            <tr>
                <td class="bold" style="color: #d9534f;">Desconto ({{ number_format($orcamento->percentual_desconto, 0) }}%):</td>
                <td style="color: #d9534f;">- R$ {{ number_format($orcamento->desconto, 2, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td class="bold">Forma de Pagamento:</td>
            <td>{{ $orcamento->forma_pagamento }}</td>
        </tr>
        <tr>
            <td class="total-final">VALOR TOTAL:</td>
            <td class="total-final">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="pix-container">
        <div class="section-title" style="border: none; padding-left: 0; background: none;">PAGAMENTO VIA PIX</div>
        
        <table width="100%">
            <tr>
                <td width="70%" valign="top">
                    <p class="mb-10"><strong>Beneficiário:</strong> {{ $banco ?? 'STOFGARD / CARLOS' }}</p>
                    @if ($chavePix)
                        <p class="mb-10"><strong>Chave PIX:</strong> {{ $chavePix }}</p>
                    @endif
                    
                    @if ($copiaCopia)
                        <p style="font-size: 8pt; margin-bottom: 5px;">Copia e Cola:</p>
                        <div class="pix-code">
                            {{ $copiaCopia }}
                        </div>
                    @endif
                    <p style="font-size: 8pt; color: #666;">
                        *Validade: Orçamento e QR Code válidos por 7 dias a partir da emissão.
                    </p>
                </td>
                <td width="30%" align="center" valign="middle">
                    @if ($qrCodePix)
                        <img src="data:image/png;base64,{{ $qrCodePix }}" alt="QR Code PIX" style="border: 1px solid #ccc; padding: 5px; max-width: 150px;">
                    @else
                         <p style="font-size: 8pt; color: #999;">QR Code não disponível</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento não representa um contrato firmado. Após a aprovação, será gerada uma Ordem de Serviço oficial.<br>
        Documento gerado em {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
