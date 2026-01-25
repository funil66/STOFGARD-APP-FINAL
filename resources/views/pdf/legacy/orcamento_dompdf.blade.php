<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Orçamento #{{ $orcamento->numero_orcamento }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        
        /* Cores Hardcoded (DomPDF não aceita var) */
        .text-brand { color: #004aad; }
        .bg-brand { background-color: #004aad; color: white; }
        .text-accent { color: #f59e0b; }
        
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        
        /* Header */
        .header-table { margin-bottom: 20px; border-bottom: 2px solid #004aad; padding-bottom: 10px; }
        .logo { max-height: 80px; }
        .company-info { text-align: right; font-size: 9px; color: #555; }
        .company-name { font-size: 18px; font-weight: 800; color: #004aad; letter-spacing: 1px; }
        
        /* Meta Data (Cliente / Orçamento) */
        .meta-table { margin-bottom: 10px; }
        .section-title { background-color: #f6f8fb; color: #004aad; font-weight: 800; padding: 6px 10px; border-left: 6px solid #004aad; margin-top: 15px; margin-bottom: 5px; font-size: 11px; text-transform: uppercase; }
        
        .client-table td { padding: 2px 0; }
        .label { font-weight: 700; width: 15%; }
        
        /* Itens */
        .category-header { font-weight: 700; padding: 6px 0; margin-top: 10px; border-bottom: 1px solid #eee; display: block; margin-bottom: 4px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; color: white; font-weight: bold; font-size: 9px; margin-right: 8px; }
        .bg-higi { background-color: #004aad; }
        .bg-imper { background-color: #f59e0b; }
        
        .desc-text { font-size: 10px; color: #444; margin-bottom: 5px; font-style: italic; display: block; }
        
        .items-table th { background: #004aad; color: #fff; padding: 6px; font-size: 9px; text-transform: uppercase; text-align: left; }
        .items-table td { padding: 6px; border-bottom: 1px solid #e6e6e6; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* Totais e PIX */
        .footer-layout-table { margin-top: 20px; }
        
        .totals-box { width: 100%; }
        .totals-row td { padding: 4px; text-align: right; }
        .total-final { font-size: 14px; font-weight: 800; color: #004aad; border-top: 2px solid #004aad; padding-top: 5px; }
        
        .pix-box { background: #fcfcfc; border: 1px solid #e6e6e6; padding: 10px; }
        .pix-title { font-weight: 700; color: #059669; margin-bottom: 5px; font-size: 10px; }
        
        /* Footer */
        .page-footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="50%">
                @if(file_exists(public_path('images/logo-stofgard.png')))
                    <img src="{{ public_path('images/logo-stofgard.png') }}" class="logo">
                @else
                    <div class="company-name">STOFGARD</div>
                @endif
            </td>
            <td width="50%" class="company-info">
                <div class="company-name" style="font-size: 14px; margin-bottom: 5px;">STOFGARD</div>
                Higienização & Impermeabilização<br>
                CNPJ: 58.794.846/0001-20<br>
                (16) 99104-0195 | contato@stofgard.com.br
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td width="60%" style="vertical-align: top; padding-right: 20px;">
                <div class="section-title" style="margin-top:0;">DADOS DO CLIENTE</div>
                <table class="client-table">
                    <tr><td class="label">Cliente:</td><td>{{ $orcamento->cliente->nome }}</td></tr>
                    @if($orcamento->cliente->email)
                    <tr><td class="label">E-mail:</td><td>{{ $orcamento->cliente->email }}</td></tr>
                    @endif
                    <tr><td class="label">Tel:</td><td>{{ $orcamento->cliente->telefone ?? $orcamento->cliente->celular }}</td></tr>
                    @if($orcamento->cliente->endereco)
                    <tr><td class="label">Endereço:</td><td>{{ $orcamento->cliente->endereco }}</td></tr>
                    @endif
                </table>
            </td>
            <td width="40%" style="vertical-align: top;">
                <div style="text-align: right; border-bottom: 2px solid #004aad; padding-bottom: 5px; margin-bottom: 5px;">
                    <div style="font-weight: 700; color: #004aad; font-size: 12px;">{{ $orcamento->numero_orcamento }}</div>
                    <div style="font-size: 9px; color: #666;">Emissão: {{ $orcamento->data_orcamento ? \Carbon\Carbon::parse($orcamento->data_orcamento)->format('d/m/Y') : date('d/m/Y') }}</div>
                    <div style="font-size: 9px; color: #666;"><strong>Válido até: {{ $orcamento->data_validade ? \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') : '7 dias' }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">ITENS DO ORÇAMENTO</div>

    @php
        $itensHigi = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'higienizacao');
        $itensImper = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'impermeabilizacao');
        $itensOutros = $orcamento->itens->filter(fn($i) => !$i->tabelaPreco);
    @endphp

    @if($itensHigi->count() > 0)
        <div style="margin-bottom: 15px;">
            <div style="margin-bottom: 5px;">
                <span class="badge bg-higi">HIGIENIZAÇÃO</span>
            </div>
            [cite_start]<span class="desc-text">Biossanitização Profunda: Extração de alta pressão para eliminação de biofilmes, ácaros e bactérias[cite: 58].</span>
            <table class="items-table">
                <thead>
                    <tr><th width="50%">DESCRIÇÃO</th><th width="10%" class="text-center">UN</th><th width="10%" class="text-center">QTD</th><th width="15%" class="text-right">VALOR UN.</th><th width="15%" class="text-right">SUBTOTAL</th></tr>
                </thead>
                <tbody>
                    @foreach($itensHigi as $item)
                    <tr>
                        <td>{{ $item->descricao_item }}</td>
                        <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                        <td class="text-center">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($itensImper->count() > 0)
        <div style="margin-bottom: 15px;">
            <div style="margin-bottom: 5px;">
                <span class="badge bg-imper">IMPERMEABILIZAÇÃO</span>
            </div>
            [cite_start]<span class="desc-text">Escudo hidrofóbico invisível que repele líquidos e óleos, preservando a integridade das fibras e prolongando a vida útil[cite: 63].</span>
            <table class="items-table">
                <thead>
                    <tr><th width="50%">DESCRIÇÃO</th><th width="10%" class="text-center">UN</th><th width="10%" class="text-center">QTD</th><th width="15%" class="text-right">VALOR UN.</th><th width="15%" class="text-right">SUBTOTAL</th></tr>
                </thead>
                <tbody>
                    @foreach($itensImper as $item)
                    <tr>
                        <td>{{ $item->descricao_item }}</td>
                        <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                        <td class="text-center">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <table class="footer-layout-table">
        <tr>
            <td width="60%" style="padding-right: 15px;">
                <div class="pix-box">
                    <div class="pix-title">PIX (informações)</div>
                    <table width="100%">
                        <tr>
                            <td width="80">
                                @if(isset($qrCodePix) || $orcamento->pix_qrcode_base64)
                                    <img src="{{ $qrCodePix ?? $orcamento->pix_qrcode_base64 }}" style="width: 70px; height: 70px; border: 1px solid #ddd;">
                                @else
                                    <div style="width:70px;height:70px;background:#eee;text-align:center;line-height:70px;font-size:8px;">SEM QR</div>
                                @endif
                            </td>
                            <td style="padding-left: 10px; font-size: 9px;">
                                <strong>Chave:</strong> <span style="font-family: monospace">{{ $orcamento->pix_chave_valor ?? '58.794.846/0001-20' }}</span><br>
                                <strong>Banco:</strong> Inter<br>
                                @if(isset($copiaCopia) || $orcamento->pix_copia_cola)
                                <div style="margin-top: 5px; font-size: 8px; color: #666; word-break: break-all;">
                                    <strong>Copia e Cola:</strong><br>
                                    {{ substr($copiaCopia ?? $orcamento->pix_copia_cola, 0, 40) }}...
                                </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td width="40%">
                <table class="totals-box">
                    <tr class="totals-row"><td>Subtotal:</td><td>R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</td></tr>
                    @if($orcamento->valor_desconto > 0)
                    <tr class="totals-row"><td style="color: #f59e0b;">Desconto PIX:</td><td style="color: #f59e0b;">- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</td></tr>
                    @endif
                    @if($orcamento->acrescimo > 0)
                    <tr class="totals-row"><td>Acréscimo:</td><td>+ R$ {{ number_format($orcamento->acrescimo, 2, ',', '.') }}</td></tr>
                    @endif
                    <tr class="totals-row"><td class="total-final">VALOR TOTAL:</td><td class="total-final">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if($orcamento->observacoes)
    <div style="margin-top: 20px; background: #fff8e1; border-left: 4px solid #f59e0b; padding: 10px; font-size: 9px;">
        <strong>OBSERVAÇÕES:</strong><br>
        {!! nl2br(e($orcamento->observacoes)) !!}
    </div>
    @endif

    <div class="page-footer">
        Validade: Orçamento válido por 7 dias. Documento gerado em {{ date('d/m/Y H:i') }}.<br>
        Stofgard Higienização & Impermeabilização
    </div>

</body>
</html>
