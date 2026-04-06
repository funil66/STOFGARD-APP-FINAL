<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $orcamento->numero }}</title>
    @php
        $primary = data_get($config, 'pdf_color_primary', '#2563eb');
        $secondary = data_get($config, 'pdf_color_secondary', '#eff6ff');
        $text = data_get($config, 'pdf_color_text', '#1f2937');
    @endphp
    <style>
        @page {
            margin: 80px 40px 90px 40px;
            margin-left: 40px;
            margin-right: 40px;
            margin-top: 80px;
            margin-bottom: 90px;
        }

        @page :first {
            margin-top: 40px;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
            color: {{ $text }};
            line-height: 1.3;
        }

        /* Espaçamento para header/footer em todas as páginas */
        body::before {
            content: '';
            display: block;
            height: 0;
        }

        /* UTILITARIOS */
        .w-full {
            width: 100%;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-blue {
            color: {{ $primary }};
        }

        .text-slate {
            color: #64748b;
        }

        /* HEADER */
        .header-table {
            margin-bottom: 25px;
            border-bottom: 2px solid {{ $primary }};
            padding-bottom: 10px;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        .logo-img {
            max-height: 70px;
            display: block;
        }

        .orc-number {
            font-size: 18px;
            color: #ffffff;
            font-weight: 800;
        }

        .doc-id-box {
            background: {{ $primary }};
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            display: inline-block;
            min-width: 180px;
            text-align: right;
        }

        /* SEÇÕES */
        .section-bar {
            background-color: {{ $secondary }};
            color: {{ $text }};
            font-weight: 800;
            font-size: 10px;
            padding: 6px 10px;
            border-left: 4px solid {{ $primary }};
            margin: 20px 0 10px 0;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        /* TABELA DE ITENS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            page-break-inside: avoid;
        }

        .items-table th {
            background: #f8fafc;
            text-align: left;
            padding: 6px;
            font-size: 9px;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }

        .group-header {
            color: {{ $primary }};
            font-weight: bold;
            font-size: 10px;
            margin-top: 10px;
            display: block;
            page-break-after: avoid;
        }

        /* --- NOVO LAYOUT FINANCEIRO (GRID DE 3 COLUNAS SIMULADO) --- */
        .finance-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .finance-grid td {
            vertical-align: top;
            padding: 15px;
            page-break-inside: avoid;
        }

        /* COLUNA 1: TOTAIS */
        .col-summary {
            width: 30%;
            background-color: #f8fafc;
            border-right: 1px dashed #e2e8f0;
            page-break-inside: avoid;
        }

        .summary-row {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .summary-label {
            display: block;
            font-size: 9px;
            color: #64748b;
        }

        .summary-value {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #334155;
        }

        .summary-total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }

        .big-price {
            font-size: 16px;
            color: #16a34a;
            font-weight: 900;
        }

        /* COLUNA 2: PARCELAMENTO */
        .col-installments {
            width: 35%;
            border-right: 1px dashed #e2e8f0;
            page-break-inside: avoid;
        }

        .parcelamento-box {
            page-break-inside: avoid;
            margin-top: 15px;
            font-size: 10px;
            border-top: 1px dashed #ddd;
            padding: 10px;
            background-color: #fafaf9;
            border-radius: 4px;
            break-inside: avoid;
        }

        .inst-table {
            width: 100%;
            font-size: 9px;
            page-break-inside: avoid;
        }

        .inst-table td {
            padding: 4px 0;
            page-break-inside: avoid;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Seções de conteúdo que não devem quebrar no meio */
        .section-bar {
            page-break-after: avoid;
        }

        .values-box,
        .pix-box {
            page-break-inside: avoid;
        }

        /* COLUNA 3: PIX (O FIX) */
        .col-pix {
            width: 35%;
            background-color: #f0fdf4;
            text-align: center;
            page-break-inside: avoid;
        }

        .qr-wrapper {
            background: #fff;
            padding: 5px;
            display: inline-block;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            page-break-inside: avoid;
        }

        .qr-img {
            width: 100px;
            height: 100px;
            display: block;
        }

        /* A CORREÇÃO DO COPIA E COLA */
        .payload-container {
            margin-top: 10px;
            text-align: left;
            page-break-inside: avoid;
        }

        .payload-label {
            font-size: 8px;
            font-weight: bold;
            color: #166534;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .payload-box {
            background: #fff;
            border: 1px dashed #16a34a;
            color: #334155;
            font-family: 'Courier New', monospace;
            /* Fonte monoespaçada ajuda na quebra */
            font-size: 8px;
            padding: 6px;
            border-radius: 3px;
            width: 100%;

            /* AS REGRAS MÁGICAS PARA QUEBRAR O TEXTO */
            white-space: pre-wrap;
            /* Mantém quebras se houver, mas quebra se precisar */
            word-wrap: break-word;
            /* Quebra palavras longas */
            word-break: break-all;
            /* Quebra em qualquer caractere se precisar */
            overflow-wrap: break-word;
            /* Padrão moderno */
            display: block;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            background: #fff;
        }
    </style>
</head>

<body>

    <div class="footer">
        Documento válido até <strong>{{ \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') }}</strong>.
        Emitido em {{ now()->format('d/m/Y H:i') }}
    </div>

    <table class="header-table w-full">
        <tr>
            <td width="60%" valign="top">
                @if(!empty($config['empresa_logo']) && file_exists(public_path('storage/' . $config['empresa_logo'])))
                    <img src="{{ asset('storage/' . $config['empresa_logo']) }}" class="logo-img">
                @else
                    <h2 class="text-blue" style="margin:0;">{{ $config['empresa_nome'] ?? 'AUTONOMIA ILIMITADA' }}</h2>
                @endif
                <div class="text-slate" style="font-size:9px; margin-top:5px;">
                    {{ $config['empresa_nome'] ?? 'Autonomia Ilimitada' }}<br>
                    CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>
                    {{ $config['empresa_telefone'] ?? '' }}
                </div>
            </td>
            <td width="40%" class="text-right" valign="bottom">
                <div class="doc-id-box">
                    <div style="font-size:9px; opacity: .9;">ORÇAMENTO</div>
                    <div class="orc-number">{{ $orcamento->numero }}</div>
                    <div style="margin-top:5px; font-weight:bold; font-size:11px;">
                        {{ strtoupper($orcamento->cliente->nome) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-bar">DADOS DO CLIENTE</div>
    <table class="w-full" style="margin-bottom:10px;">
        <tr>
            <td width="60%"><strong>Nome:</strong> {{ $orcamento->cliente->nome }}</td>
            <td width="40%"><strong>Tel:</strong> {{ $orcamento->cliente->telefone }}</td>
        </tr>
        <tr>
            <td><strong>Email:</strong> {{ $orcamento->cliente->email }}</td>
            <td><strong>Cidade:</strong> {{ $orcamento->cliente->cidade }}/{{ $orcamento->cliente->estado }}</td>
        </tr>
    </table>

    <div class="section-bar">SERVIÇOS</div>
    @foreach(['higienizacao' => 'HIGIENIZAÇÃO', 'impermeabilizacao' => 'IMPERMEABILIZAÇÃO'] as $tipo => $label)
        @php $itensTipo = $orcamento->itens->filter(fn($i) => $i->servico_tipo === $tipo); @endphp
        @if($itensTipo->isNotEmpty())
            <span class="group-header">{{ $label }}</span>
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="60%">DESCRIÇÃO</th>
                        <th width="10%">QTD</th>
                        <th width="15%" class="text-right">UNIT</th>
                        <th width="15%" class="text-right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itensTipo as $item)
                        <tr>
                            <td>{{ $item->item_nome }}</td>
                            <td class="text-center">{{ number_format($item->quantidade, 0) }}</td>
                            <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                            <td class="text-right font-bold">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    {{-- Extras (Dados Personalizados por Nicho) --}}
    @if($orcamento->extra_attributes && is_array($orcamento->extra_attributes))
        @foreach($orcamento->extra_attributes as $key => $val)
            @if(is_numeric($val) && $val > 0)
                <table class="items-table" style="margin-top: 8px; border: 1px dashed #94a3b8;">
                    <tbody>
                        <tr style="background-color: #f8fafc;">
                            <td width="85%" style="text-align:right; font-weight: bold; color: #334155;">{{ ucfirst($key) }}</td>
                            <td width="15%" style="text-align:right; font-weight: bold; color: #0f766e;">R$
                                {{ number_format($val, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    @php
        // Calcula total dos itens
        $total = $orcamento->itens->sum('subtotal');

        // Adiciona valores de extra_attributes (Dados Personalizados por Nicho)
        if ($orcamento->extra_attributes && is_array($orcamento->extra_attributes)) {
            foreach ($orcamento->extra_attributes as $key => $val) {
                if (is_numeric($val) && $val > 0) {
                    $total += (float) $val;
                }
            }
        }

        // Percentual de desconto PIX
        $percDesconto = $config['financeiro_desconto_avista'] ?? 10;

        // Total à vista com desconto PIX (apenas para exibição, não altera o total)
        $totalAvista = $total * (1 - ($percDesconto / 100));
        $valorDesconto = $total - $totalAvista;

        // Regras de parcelamento
        $regras = $config['financeiro_parcelamento'] ?? [];

        // Exibir desconto PIX no resumo? (independente do QR Code)
        $mostrarDescontoPix = (bool) $orcamento->aplicar_desconto_pix;

        // Exibir QR Code no PDF? (independente do desconto)
        $mostrarQrCode = (bool) ($orcamento->pdf_incluir_pix && $orcamento->pix_qrcode_base64);

        // Dados do PIX (para o QR Code)
        $pix = [
            'img' => $orcamento->pix_qrcode_base64 ?? null,
            'payload' => $orcamento->pix_copia_cola ?? null,
            'txid' => $orcamento->numero ?? 'N/A',
            'beneficiario' => $config['empresa_nome'] ?? 'Autonomia Ilimitada',
        ];
    @endphp

    <div class="section-bar">RESUMO & PAGAMENTO</div>

    <table class="finance-grid">
        <tr>
            <td class="col-summary">
                <div class="summary-row">
                    <span class="summary-label">SUBTOTAL</span>
                    <span class="summary-value">R$ {{ number_format($total, 2, ',', '.') }}</span>
                </div>

                @if($mostrarDescontoPix)
                    {{-- Exibe desconto PIX sem alterar o valor total --}}
                    <div class="summary-row" style="margin-top:6px;">
                        <span class="summary-label" style="color:#166534;">DESCONTO PIX ({{ $percDesconto }}%)</span>
                        <span class="summary-value" style="color:#16a34a;">- R$
                            {{ number_format($valorDesconto, 2, ',', '.') }}</span>
                    </div>
                    <div class="summary-total">
                        <span class="summary-label" style="color:#166534;">TOTAL À VISTA (PIX)</span>
                        <span class="big-price">R$ {{ number_format($totalAvista, 2, ',', '.') }}</span>
                    </div>
                    <div style="font-size:8px; color:#94a3b8; margin-top:4px;">
                        * Valor à vista para pagamento via PIX
                    </div>
                @else
                    <div class="summary-total">
                        <span class="summary-label">TOTAL</span>
                        <span class="big-price">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                @endif
            </td>

            <td class="col-installments">
                <div style="font-weight:bold; font-size:10px; margin-bottom:5px; color:#334155;">NO CARTÃO:</div>
                @if(($orcamento->pdf_mostrar_parcelamento ?? true) && count($regras) > 0)
                    <table class="inst-table">
                        @foreach($regras as $r)
                            <tr>
                                <td><strong>{{ $r['parcelas'] }}x</strong> R$
                                    {{ number_format(($total * (1 + ($r['taxa'] / 100))) / $r['parcelas'], 2, ',', '.') }}
                                </td>
                                <td class="text-right text-slate">Total:
                                    {{ number_format($total * (1 + ($r['taxa'] / 100)), 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <span class="text-slate" style="font-size:9px;">Consulte condições.</span>
                @endif
            </td>

            {{-- Coluna PIX: controlada APENAS por pdf_incluir_pix (independente do desconto) --}}
            <td class="col-pix">
                @if($mostrarQrCode)
                    <div style="font-weight:bold; color:#166534; margin-bottom:5px; font-size:11px;">PAGUE COM PIX</div>

                    @if($pix['img'])
                        <div class="qr-wrapper">
                            <img src="{{ $pix['img'] }}" class="qr-img">
                        </div>
                    @else
                        <div style="color:#ccc; font-size:9px; padding:20px;">QR indisponível</div>
                    @endif

                    <div class="payload-container">
                        <div class="payload-label">Copia e Cola:</div>
                        <div class="payload-box">
                            @if(!empty($pix['payload']))
                                {{ $pix['payload'] }}
                            @else
                                Erro: Chave não gerada.
                            @endif
                        </div>
                    </div>

                    <div style="font-size:8px; color:#64748b; margin-top:5px; text-align:left;">
                        <strong>Ref:</strong> {{ $pix['txid'] ?? '-' }}<br>
                        <strong>Fav:</strong> {{ substr($pix['beneficiario'], 0, 15) }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

</body>

</html>