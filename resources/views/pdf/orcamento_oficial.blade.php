<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orcamento {{ $orcamento->numero_orcamento }}</title>
    <style>
        @page { size: A4; margin: 8mm 10mm; }
        body{font-family:Arial,Helvetica,sans-serif;font-size:9px;color:#333;line-height:1.2}

        /* Palette: logo blue + green accent */
        :root{--brand:#1E6DD8;--accent:#059669;--muted:#f3f4f6}

        /* Utilities */
        .text-right{text-align:right}.text-center{text-align:center}.bold{font-weight:700}.uppercase{text-transform:uppercase}

        /* HEADER: reuse current header style but keep markup consistent with main template */
        .header-tbl{width:100%;border-bottom:2px solid var(--brand);margin-bottom:8px;padding-bottom:5px}
        .logo-box{width:60%;vertical-align:middle}
        .meta-box{width:40%;vertical-align:middle;text-align:right}
        .brand{font-size:18px;font-weight:700;color:var(--brand)}
        .tagline{font-size:9px;color:#666}
        .company-data{font-size:8px;color:#555}

        /* Client bar */
        .client-bar{background:var(--muted);border-left:4px solid var(--brand);padding:6px;margin-bottom:8px;border-radius:0 4px 4px 0}
        .client-tbl{width:100%}.client-tbl td{padding:1px 6px;font-size:9px}
        .lbl{color:var(--brand);font-weight:700;margin-right:6px}

        /* Items table slim */
        .items-tbl{width:100%;border-collapse:collapse;margin-bottom:8px}
        .items-tbl th{background:var(--brand);color:#fff;padding:4px 6px;font-size:8px;text-align:left}
        .items-tbl td{padding:4px 6px;border-bottom:1px solid #eee;font-size:9px;vertical-align:top}
        .items-tbl tr:nth-child(even){background:#f9fafb}

        .badge-type{font-size:8px;font-weight:700;padding:2px 6px;border-radius:3px;margin-top:6px;display:inline-block}
        .bg-higi{background:#dbeafe;color:#1e40af}
        .bg-imper{background:#fef3c7;color:#92400e}

        /* Observacoes */
        .obs-box{background:#fffbe6;border:1px solid #fcd34d;padding:6px;font-size:8px;text-align:justify;border-radius:4px}

        /* Payment / Totals area */
        .payment-container{width:100%;border-collapse:collapse;margin-top:6px;border:1px solid #e5e7eb;border-radius:6px}
        .pay-left{width:60%;padding:8px;vertical-align:top;border-right:1px dashed #ddd}
        .pay-right{width:40%;padding:8px;vertical-align:top;background:#f9fafb}

        /* PIX BOX: green frame, reduced height but QR stays same */
        .pix-box{background:#ecfdf5;border:1px solid #10b981;border-radius:6px;padding:6px;height:80px;overflow:hidden;box-sizing:border-box}
        .pix-box table{height:100%}
        .pix-box .pix-tbl td{vertical-align:middle}
        .qr-code{width:65px;height:65px;border:1px solid #ddd;padding:1px;display:block;margin:0 auto}
        .pix-info{padding-left:8px;vertical-align:middle;font-size:9px}
        .validade-alert{margin-top:6px;font-size:8px;color:#9a3412;background:#ffedd5;padding:4px;border-radius:3px;font-weight:700;text-align:center;border:1px solid #fed7aa}

        .total-tbl{width:100%}.total-tbl td{padding:2px 0;text-align:right}
        .total-final{font-size:13px;color:var(--brand);font-weight:700;border-top:1px solid #ddd;padding-top:4px}

        /* Footer small */
        .footer{border-top:1px solid #ddd;padding-top:6px;margin-top:12px;text-align:center;font-size:7px;color:#888}

        /* Ensure page-breaks are avoided inside key boxes */
        .obs-box, .payment-container, .items-tbl, .total-tbl{page-break-inside:avoid}

    </style>
</head>
<body>
    <!-- Header: reuse markup (do not modify main header file) -->
    <table class="header-tbl">
        <tr>
            <td class="logo-box">
                @if(file_exists(public_path('images/logo-stofgard.png')))
                    <img src="{{ public_path('images/logo-stofgard.png') }}" alt="logo" style="max-width:120px;height:auto;display:block">
                @else
                    <div class="brand">STOFGARD</div>
                    <div class="tagline">Higienização e Impermeabilização de Estofados</div>
                @endif
                <div class="company-data">CNPJ: 58.794.846/0001-20<br>(16) 99104-0195 | contato@stofgard.com.br</div>
            </td>
            <td class="meta-box">
                <div style="display:inline-block;background:#f0f7ff;border:1px solid var(--brand);padding:6px;border-radius:4px;text-align:left">
                    <div style="font-weight:700;color:var(--brand);font-size:12px">ORÇAMENTO #{{ $orcamento->numero_orcamento }}</div>
                    <div style="font-size:9px">Emissão: {{ $orcamento->data_orcamento->format('d/m/Y') }}</div>
                    <div style="font-size:9px">Validade: {{ $orcamento->data_validade->format('d/m/Y') }}</div>
                    @if($orcamento->user) <div style="font-size:9px">Consultor: {{ $orcamento->user->name }}</div> @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="client-bar">
        <table class="client-tbl">
            <tr>
                <td width="50%"><span class="lbl">Cliente:</span> {{ $orcamento->cliente->nome }}</td>
                <td width="50%"><span class="lbl">Tel:</span> {{ $orcamento->cliente->celular }}</td>
            </tr>
            <tr>
                <td><span class="lbl">Email:</span> {{ $orcamento->cliente->email ?? '-' }}</td>
                <td><span class="lbl">Local:</span> {{ Str::limit($orcamento->cliente->endereco,45) ?? 'Não informado' }}</td>
            </tr>
        </table>
    </div>

    @php
        $grupos = [
            'higienizacao' => ['nome' => 'HIGIENIZAÇÃO', 'class' => 'bg-higi', 'itens' => $orcamento->itens->filter(fn($i) => $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'higienizacao')],
            'impermeabilizacao' => ['nome' => 'IMPERMEABILIZAÇÃO (BLINDAGEM)', 'class' => 'bg-imper', 'itens' => $orcamento->itens->filter(fn($i) => $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'impermeabilizacao')],
            'outros' => ['nome' => 'OUTROS SERVIÇOS', 'class' => 'bg-higi', 'itens' => $orcamento->itens->filter(fn($i) => !$i->tabelaPreco)]
        ];
    @endphp

    @foreach($grupos as $tipo => $grupo)
        @if($grupo['itens']->count() > 0)
            <div class="badge-type {{ $grupo['class'] }}">{{ $grupo['nome'] }}</div>
            <table class="items-tbl">
                <thead>
                    <tr>
                        <th width="58%">Descrição</th>
                        <th width="8%" class="text-center">Unid.</th>
                        <th width="10%" class="text-right">Qtd.</th>
                        <th width="12%" class="text-right">Unit.</th>
                        <th width="12%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo['itens'] as $item)
                    <tr>
                        <td>{{ $item->descricao_item }}</td>
                        <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'm²' : 'un' }}</td>
                        <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right bold">{{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <table class="bottom-layout" style="width:100%;margin-top:6px;border-collapse:collapse">
        @if($orcamento->observacoes)
        <tr>
            <td style="width:100%;padding-bottom:8px">
                <div style="font-weight:700;color:var(--brand);font-size:10px;margin-bottom:4px">OBSERVAÇÕES TÉCNICAS</div>
                <div class="obs-box">{!! nl2br(e($orcamento->observacoes)) !!}</div>
            </td>
        </tr>
        @endif

        <tr>
            <td>
                <table class="payment-container">
                    <tr>
                        <td class="pay-left">
                            <div class="pix-box">
                                <table class="pix-tbl" style="width:100%;border-collapse:collapse">
                                    <tr>
                                        <td width="70" style="vertical-align:middle;text-align:center">
                                            @if(($qrCodePix ?? null) || $orcamento->pix_qrcode_base64)
                                                <img src="{{ $qrCodePix ?? $orcamento->pix_qrcode_base64 }}" class="qr-code" alt="QR">
                                            @endif
                                        </td>
                                        <td class="pix-info" style="vertical-align:middle">
                                            <div class="bold" style="color:var(--accent);font-size:10px">PAGAMENTO VIA PIX</div>
                                            <div style="font-size:8px;margin-bottom:2px">Desconto já aplicado no total.</div>
                                            <div style="font-size:9px">Chave CNPJ: <strong>58.794.846/0001-20</strong></div>
                                            <div style="font-size:8px;color:#666">Banco Inter</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="validade-alert">⚠ Este orçamento e o QR Code expiram em 7 dias após a emissão.</div>
                        </td>

                        <td class="pay-right">
                            <table class="total-tbl">
                                <tr>
                                    <td>Subtotal:</td>
                                    <td>R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</td>
                                </tr>
                                @if($orcamento->valor_desconto > 0)
                                <tr>
                                    <td style="color:var(--accent)">Desconto:</td>
                                    <td style="color:var(--accent)">- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="total-final" style="vertical-align:bottom">TOTAL:</td>
                                    <td class="total-final">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">Stofgard - Higienização e Impermeabilização | CNPJ: 58.794.846/0001-20<br>Este documento não representa um contrato firmado. Após a aprovação, será gerada uma OS oficial.</div>

</body>
</html>