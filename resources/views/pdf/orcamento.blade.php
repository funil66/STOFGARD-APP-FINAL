<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $orcamento->numero ?? $orcamento->numero_orcamento }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 12mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }

        /* HEADER */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            max-width: 55%;
        }

        .logo-img {
            max-width: 200px;
            max-height: 70px;
            margin-bottom: 8px;
        }

        .company-info {
            font-size: 8.5px;
            color: #374151;
            line-height: 1.6;
        }

        .header-right {
            background: #2563eb;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: right;
            min-width: 170px;
        }

        .numero-orcamento {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .datas {
            font-size: 8px;
            line-height: 1.7;
        }

        /* SECTIONS */
        .section-header {
            background: #2563eb;
            color: white;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* CLIENT */
        .client-box {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .client-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .client-name {
            font-size: 11px;
            font-weight: bold;
        }

        .client-detail {
            font-size: 9px;
            color: #6b7280;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table thead {
            background: #f3f4f6;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }

        table thead th:nth-child(3),
        table thead th:nth-child(4),
        table thead th:nth-child(5) {
            text-align: right;
        }

        table tbody td {
            padding: 8px 6px;
            font-size: 9px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table tbody td:nth-child(3),
        table tbody td:nth-child(4),
        table tbody td:nth-child(5) {
            text-align: right;
        }

        .item-category {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 7px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .cat-higienizacao {
            background: #dbeafe;
            color: #1e40af;
        }

        .cat-impermeabilizacao {
            background: #fef3c7;
            color: #92400e;
        }

        .cat-outro {
            background: #e5e7eb;
            color: #374151;
        }

        .item-description {
            color: #6b7280;
            font-size: 8px;
            line-height: 1.3;
        }

        /* VALUES SECTION - 2 COLUMNS */
        .valores-section {
            margin-top: 16px;
            display: flex;
            gap: 20px;
        }

        .valores-left {
            flex: 1;
        }

        .valores-right {
            width: 220px;
        }

        .valores-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
        }

        .valor-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 10px;
        }

        .valor-row.desconto {
            color: #dc2626;
            font-weight: 600;
        }

        .valor-row.desconto-prestador {
            color: #ea580c;
            font-weight: 600;
        }

        .valor-row-separator {
            border-top: 2px solid #2563eb;
            margin: 8px 0;
        }

        .valor-total-box {
            background: #eff6ff;
            border: 2px solid #2563eb;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            margin-top: 10px;
        }

        .valor-total-label {
            font-size: 11px;
            color: #1e40af;
            font-weight: 600;
        }

        .valor-total-value {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }

        /* PIX BOX */
        .pix-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .pix-title {
            font-size: 11px;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 8px;
        }

        .pix-qrcode img {
            width: 100px;
            height: 100px;
            border: 2px solid #10b981;
            border-radius: 4px;
            background: white;
            padding: 3px;
        }

        .pix-valor {
            margin-top: 8px;
            font-size: 12px;
            font-weight: bold;
            color: #065f46;
        }

        .pix-desconto {
            font-size: 9px;
            color: #059669;
        }

        .pix-chave {
            margin-top: 8px;
            font-size: 7px;
            color: #374151;
        }

        .pix-code {
            background: white;
            border: 1px solid #10b981;
            border-radius: 4px;
            padding: 5px;
            font-family: 'Courier New', monospace;
            font-size: 6px;
            word-break: break-all;
            color: #111;
            line-height: 1.4;
            margin-top: 5px;
        }

        /* FOOTER */
        .footer {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 8px;
            color: #dc2626;
            text-align: center;
            margin-bottom: 8px;
        }

        .footer-legal {
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            @php
                $logoPath = null;
                if (isset($config->empresa_logo) && $config->empresa_logo) {
                    $logoPath = $config->empresa_logo;
                    if (!file_exists($logoPath)) {
                        $logoPath = storage_path('app/public/' . $config->empresa_logo);
                    }
                } else {
                    $manualPath = storage_path('app/public/logos/logo-stofgard.png');
                    if (file_exists($manualPath)) {
                        $logoPath = $manualPath;
                    }
                }
            @endphp

            @if($logoPath && file_exists($logoPath))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo"
                    class="logo-img">
            @else
                <div style="font-size: 16px; font-weight: bold; color: #2563eb; margin-bottom: 8px;">
                    STOFGARD
                </div>
                <div style="font-size: 10px; color: #6b7280;">Higienização e Impermeabilização</div>
            @endif

            <div class="company-info">
                <div><strong>CNPJ:</strong> {{ $config->empresa_cnpj ?? '00.000.000/0001-00' }}</div>
                <div><strong>Telefone:</strong> {{ $config->empresa_telefone ?? '(16) 99999-9999' }}</div>
                <div><strong>E-mail:</strong> {{ $config->empresa_email ?? 'contato@stofgard.com.br' }}</div>
            </div>
        </div>
        <div class="header-right">
            <div class="numero-orcamento">{{ $orcamento->numero ?? $orcamento->numero_orcamento }}</div>
            <div class="datas">
                <div><strong>Data de Emissão:</strong><br>
                    {{ $orcamento->data_orcamento ? \Carbon\Carbon::parse($orcamento->data_orcamento)->format('d/m/Y H:i') : $orcamento->created_at->format('d/m/Y H:i') }}
                </div>
                <div style="margin-top: 4px;"><strong>Válido até:</strong><br>
                    {{ $orcamento->data_validade ? \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') : ($orcamento->created_at ?? now())->addDays(15)->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- DADOS DO CLIENTE -->
    <div class="section-header">DADOS DO CLIENTE</div>
    <div class="client-box">
        <div class="client-row">
            <span class="client-name">Cliente:
                {{ strtoupper($orcamento->cliente->nome ?? 'Cliente Não Informado') }}</span>
        </div>
        <div class="client-row">
            <span class="client-detail">Telefone:
                {{ $orcamento->cliente->telefone ?? $orcamento->cliente->celular ?? '(--) -----' }}</span>
            <span class="client-detail">E-mail: {{ $orcamento->cliente->email ?? 'Não informado' }}</span>
        </div>
    </div>

    <!-- ITENS DO ORÇAMENTO -->
    <div class="section-header">ITENS DO ORÇAMENTO</div>
    <table>
        <thead>
            <tr>
                <th style="width: 45%;">Item</th>
                <th style="width: 10%;">Un</th>
                <th style="width: 10%;">Qtd</th>
                <th style="width: 17%;">Valor Unit.</th>
                <th style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($orcamento->itens && count($orcamento->itens) > 0)
                @foreach($orcamento->itens as $item)
                    @php
                        $categoria = strtolower($item->categoria ?? 'outro');
                        $catClass = match (true) {
                            str_contains($categoria, 'higien') => 'cat-higienizacao',
                            str_contains($categoria, 'imper') => 'cat-impermeabilizacao',
                            default => 'cat-outro'
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="item-category {{ $catClass }}">
                                {{ strtoupper($item->categoria ?? 'SERVIÇO') }}
                            </span>
                            <div class="item-description">{{ $item->descricao }}</div>
                        </td>
                        <td>{{ strtoupper($item->unidade_medida ?? 'UN') }}</td>
                        <td>{{ number_format($item->quantidade, 0) }}</td>
                        <td><strong>R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</strong></td>
                        <td><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #9ca3af;">
                        Nenhum item cadastrado
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- VALORES (2 COLUNAS) -->
    <div class="valores-section">
        <!-- COLUNA ESQUERDA: VALORES -->
        <div class="valores-left">
            <div class="section-header" style="margin-top: 0;">VALORES</div>
            <div class="valores-box">
                <div class="valor-row">
                    <span>Subtotal:</span>
                    <span><strong>R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</strong></span>
                </div>

                @php
                    $valorFinal = $orcamento->valor_total;
                    $descontoPix = 0;
                    $descontoPrestador = $orcamento->desconto_prestador ?? 0;

                    // Desconto PIX
                    if ($orcamento->aplicar_desconto_pix && $config && isset($config->percentual_desconto_pix) && $config->percentual_desconto_pix > 0) {
                        $percentual = $config->percentual_desconto_pix;
                        $descontoPix = ($orcamento->valor_total * $percentual) / 100;
                        $valorFinal -= $descontoPix;
                    }

                    // Desconto do Prestador
                    if ($descontoPrestador > 0) {
                        $valorFinal -= $descontoPrestador;
                    }

                    // Usa valor editado se existir
                    if ($orcamento->valor_final_editado) {
                        $valorFinal = $orcamento->valor_final_editado;
                    }
                @endphp

                @if($descontoPix > 0)
                    <div class="valor-row desconto">
                        <span>Desconto PIX ({{ $config->percentual_desconto_pix ?? 0 }}%):</span>
                        <span>- R$ {{ number_format($descontoPix, 2, ',', '.') }}</span>
                    </div>
                @endif

                @if($descontoPrestador > 0)
                    <div class="valor-row desconto-prestador">
                        <span>Desconto Prestador:</span>
                        <span>- R$ {{ number_format($descontoPrestador, 2, ',', '.') }}</span>
                    </div>
                @endif

                <div class="valor-row">
                    <span>Forma de Pagamento:</span>
                    <span><strong>{{ strtoupper($orcamento->forma_pagamento ?? 'PIX') }}</strong></span>
                </div>
            </div>

            <div class="valor-total-box">
                <div class="valor-total-label">VALOR TOTAL:</div>
                <div class="valor-total-value">R$ {{ number_format($valorFinal, 2, ',', '.') }}</div>
            </div>
        </div>

        <!-- COLUNA DIREITA: PIX -->
        <div class="valores-right">
            @if($orcamento->pdf_incluir_pix && $orcamento->pix_qrcode_base64)
                <div class="pix-box">
                    <div class="pix-title">💚 PAGAMENTO VIA PIX</div>
                    <div class="pix-qrcode">
                        <img src="{{ $orcamento->pix_qrcode_base64 }}" alt="QR Code PIX">
                    </div>
                    <div class="pix-valor">R$ {{ number_format($valorFinal, 2, ',', '.') }}</div>
                    @if($descontoPix > 0)
                        <div class="pix-desconto">DESCONTO DE {{ $config->percentual_desconto_pix }}%</div>
                    @endif
                    @if($config->pix_chave ?? false)
                        <div class="pix-chave">
                            <strong>CHAVE PIX:</strong> {{ $config->pix_tipo_chave ?? 'TELEFONE' }}:
                            {{ $config->pix_chave ?? '' }}
                        </div>
                    @endif
                    @if($orcamento->pix_copia_cola)
                        <div class="pix-code">{{ $orcamento->pix_copia_cola }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-warning">
            ⚠️ Validade: Orçamento e QR Code PIX válidos por 7 dias a partir da emissão.
        </div>
        <div class="footer-legal">
            Este documento não representa um contrato firmado. Após a aprovação do orçamento, será gerada uma Ordem de
            Serviço oficial.<br>
            Documento gerado em {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>

</html>