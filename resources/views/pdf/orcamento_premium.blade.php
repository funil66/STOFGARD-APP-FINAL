<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Orçamento #{{ $orcamento->numero_orcamento }}</title>
    <style>
        @page { margin: 1cm 1.5cm; }
        
        /* RESET GLOBAL */
        * { box-sizing: border-box; }

        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.2; margin: 0; padding: 0; }
        
        /* Utilitários */
        .width-100 { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-bold { font-weight: bold; }
        .text-blue { color: #004aad; }
        .text-orange { color: #d97706; }
        .uppercase { text-transform: uppercase; }
        
        /* CABEÇALHO */
        .header { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #004aad; }
        .logo-img { width: 200px; height: auto; display: block; margin-bottom: 5px; }
        
        .header-box {
            background-color: #f0f7ff; 
            border: 1px solid #dbeafe;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            width: 190px;
            float: right;
        }
        .orc-number { font-size: 16px; font-weight: 800; color: #004aad; margin-bottom: 3px; display: block; }
        .header-meta { font-size: 10px; color: #555; margin-bottom: 1px; }
        
        /* TÍTULOS */
        .section-title { 
            font-size: 12px; 
            font-weight: 800; 
            color: #004aad; 
            text-transform: uppercase; 
            background-color: #f0f7ff; 
            border-left: 5px solid #004aad; 
            padding: 6px 10px; 
            margin-bottom: 10px; 
            border-radius: 0 4px 4px 0; 
            width: 100%;
            clear: both;
        }
        
        /* CLIENTE */
        .client-box { margin-bottom: 20px; }
        .info-table td { padding: 2px 0; font-size: 11px; }
        .label { font-weight: 700; color: #004aad; margin-right: 5px; }
        
        /* ITENS */
        .cat-row { margin-top: 15px; margin-bottom: 5px; vertical-align: middle; }
        .cat-btn {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            color: white;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }
        .btn-blue { background-color: #004aad; }
        .btn-orange { background-color: #d97706; }
        .cat-desc { display: inline-block; margin-left: 8px; font-size: 10px; color: #666; font-style: italic; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .items-table th { 
            text-align: left; font-size: 10px; text-transform: uppercase; color: #666; 
            border-bottom: 1px solid #ccc; padding: 4px;
        }
        .items-table td { 
            padding: 4px; 
            border-bottom: 1px solid #f3f4f6; 
            vertical-align: top; 
            font-size: 11px; 
        }
        .item-name { font-weight: 900; color: #000; display: block; margin-bottom: 1px; } 
        .item-obs { font-size: 10px; color: #555; display: block; line-height: 1.1; }
        
        /* SEÇÃO FINANCEIRA */
        .finance-wrapper { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            margin-top: 20px; 
            page-break-inside: avoid;
            gap: 15px;
        }
        
        /* ESQUERDA: TOTAIS (53%) */
        .totals-container { 
            flex: 0 0 53%; 
        }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 0; text-align: right; font-size: 12px; }
        .totals-label { color: #555; text-align: left; padding-left: 5px; }
        .totals-value { font-weight: normal; color: #333; }
        
        .final-total-row td { 
            border-top: 2px solid #004aad; 
            padding-top: 8px; 
            margin-top: 5px;
            font-size: 16px; 
        }
        .final-total-label { color: #004aad; font-weight: 800; text-transform: uppercase; text-align: left; padding-left: 5px; }
        .final-total-value { color: #004aad; font-weight: 800; }
        
        /* DIREITA: PIX (45%) */
        .pix-container { 
            flex: 0 0 45%; 
            border: 1px solid #16a34a; 
            background-color: #dcfce7; 
            border-radius: 6px; 
            padding: 8px; 
            /* SEGURANÇA PARA FECHAR O QUADRADO */
            display: flex;
            flex-direction: column;
            height: auto;
            min-height: 100px;
        }
        .pix-header { 
            color: #15803d; 
            font-weight: 800; 
            font-size: 10px; 
            margin-bottom: 6px; 
            border-bottom: 1px dashed #86efac; 
            padding-bottom: 4px;
            text-transform: uppercase;
            text-align: center;
        }
        .pix-content { display: flex; gap: 8px; margin-bottom: 5px; }
        .pix-img { width: 60px; height: 60px; border: 2px solid #fff; border-radius: 4px; background: white; }
        .pix-info { flex-grow: 1; font-size: 9px; color: #14532d; }
        
        /* Linhas do PIX */
        .pix-row { margin-bottom: 2px; line-height: 1.2; }
        .pix-label-banco { font-weight: 900; font-size: 10px; color: #166534; display: block; text-transform: uppercase; }
        .pix-text { font-size: 9px; color: #14532d; display: block; }
        
        .pix-copia-box {
            background: #fff;
            border: 1px dashed #16a34a;
            padding: 4px;
            font-family: monospace;
            font-size: 8px; 
            color: #333;
            text-align: left; 
            word-break: break-all;
            border-radius: 3px;
            line-height: 1;
            width: 100%; 
            margin-top: auto; /* Empurra para baixo se precisar */
        }

        /* RODAPÉ */
        .footer { 
            position: fixed; bottom: 0; left: 0; right: 0; 
            text-align: center; font-size: 8px; color: #9ca3af; 
            border-top: 1px solid #e5e7eb; padding-top: 8px; 
        }
        .validade-msg { color: #d97706; font-weight: 700; margin-bottom: 2px; font-size: 9px; }
    </style>
</head>
<body>

    <table class="width-100 header">
        <tr>
            <td width="60%" valign="top">
                @if(isset($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo-img">
                @else
                    <h1 class="text-blue" style="font-size:28px; margin:0;">STOFGARD</h1>
                @endif
            </td>
            <td width="40%" valign="top">
                <div class="header-box">
                    <span class="orc-number">#{{ $orcamento->numero_orcamento }}</span>
                    <div class="header-meta">Data de Emissão: <strong>{{ $orcamento->data_orcamento ? \Carbon\Carbon::parse($orcamento->data_orcamento)->format('d/m/Y') : date('d/m/Y') }}</strong></div>
                    <div class="header-meta">Validade: <strong class="text-orange">{{ $orcamento->data_validade ? \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') : '7 dias' }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="client-box">
        <div class="section-title">DADOS DO CLIENTE</div>
        <table class="info-table width-100">
            <tr>
                <td width="60%"><span class="label">NOME:</span> {{ $orcamento->cliente->nome }}</td>
                <td width="40%" class="text-right">
                    @if($orcamento->cliente->telefone || $orcamento->cliente->celular)
                        <span class="label">TELEFONE:</span> {{ $orcamento->cliente->telefone ?? $orcamento->cliente->celular }}
                    @endif
                </td>
            </tr>
            @if($orcamento->cliente->email || $orcamento->cliente->endereco)
            <tr>
                <td>
                    @if($orcamento->cliente->email)<span class="label">E-MAIL:</span> {{ $orcamento->cliente->email }}@endif
                </td>
                <td class="text-right">
                    @if($orcamento->cliente->endereco)<span class="label">ENDEREÇO:</span> {{ $orcamento->cliente->endereco }}@endif
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section-box" style="margin-bottom: 20px;">
        <div class="section-title">ITENS E SERVIÇOS</div>

        @php
            $higi = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && stripos($i->tabelaPreco->tipo_servico, 'higienizacao') !== false);
            $imper = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && stripos($i->tabelaPreco->tipo_servico, 'impermeabilizacao') !== false);
        @endphp

        @if($higi->count() > 0)
        <div style="margin-bottom: 15px;">
            <div class="cat-row">
                <span class="cat-btn btn-blue">HIGIENIZAÇÃO</span>
                <span class="cat-desc">Biossanitização Profunda: Extração de alta pressão para eliminação de biofilmes, ácaros e bactérias.</span>
            </div>
            <table class="items-table">
                <thead>
                    <tr><th width="65%">DESCRIÇÃO</th><th width="10%" class="text-center">QTD</th><th width="12%" class="text-right">UNITÁRIO</th><th width="13%" class="text-right">TOTAL</th></tr>
                </thead>
                <tbody>
                    @foreach($higi as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ str_replace(['[cite', ']'], '', $item->nome_item) }}</span>
                            @php $desc = $item->descricao ?? $item->descricao_item ?? $item->observacoes ?? null; @endphp
                            @if($desc)
                                <span class="item-obs">{{ str_replace(['[cite', ']'], '', $desc) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($imper->count() > 0)
        <div style="margin-bottom: 15px;">
            <div class="cat-row">
                <span class="cat-btn btn-orange">IMPERMEABILIZAÇÃO</span>
                <span class="cat-desc">Escudo hidrofóbico invisível que repele líquidos e óleos, preservando a integridade das fibras.</span>
            </div>
            <table class="items-table">
                <thead>
                    <tr><th width="65%">DESCRIÇÃO</th><th width="10%" class="text-center">QTD</th><th width="12%" class="text-right">UNITÁRIO</th><th width="13%" class="text-right">TOTAL</th></tr>
                </thead>
                <tbody>
                    @foreach($imper as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ str_replace(['[cite', ']'], '', $item->nome_item) }}</span>
                            @php $desc = $item->descricao ?? $item->descricao_item ?? $item->observacoes ?? null; @endphp
                            @if($desc)
                                <span class="item-obs">{{ str_replace(['[cite', ']'], '', $desc) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="section-title">RESUMO FINANCEIRO</div>
    
    <div class="finance-wrapper">
        
        <div class="totals-container" style="flex: 0 0 {{ isset($qrCodePix) ? '53%' : '100%' }};">
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Subtotal Serviços:</td>
                    <td class="totals-value">R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</td>
                </tr>
                @if($orcamento->valor_desconto > 0)
                <tr>
                    <td class="totals-label text-orange">Descontos:</td>
                    <td class="totals-value text-orange">- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="totals-label">Forma de Pagamento:</td>
                    <td class="totals-value" style="font-style: italic; color:#666;">{{ ucfirst($orcamento->forma_pagamento ?? 'À Combinar') }}</td>
                </tr>
                @endif
                
                @if($orcamento->acrescimo > 0)
                <tr>
                    <td class="totals-label">Acréscimos:</td>
                    <td class="totals-value">+ R$ {{ number_format($orcamento->acrescimo, 2, ',', '.') }}</td>
                </tr>
                @endif
                
                <tr class="final-total-row">
                    <td colspan="2">
                        <div style="display:flex; justify-content:space-between;">
                            <span class="final-total-label">VALOR TOTAL:</span>
                            <span class="final-total-value">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        @if(isset($qrCodePix))
        <div class="pix-container">
            <div class="pix-header">PAGAMENTO VIA PIX</div>
            <div class="pix-content">
                <img src="{{ $qrCodePix }}" class="pix-img">
                <div class="pix-info">
                    <div class="pix-row">
                        <span class="pix-label-banco">{{ $pixConfig['banco'] }}</span>
                    </div>
                    <div class="pix-row">
                        <span class="pix-text">Chave Pix Celular: {{ $pixConfig['chave'] }}</span>
                    </div>
                    <div class="pix-row">
                        <span class="pix-text">Beneficiário - {{ $pixConfig['beneficiario'] }}</span>
                    </div>
                    <div class="pix-row" style="margin-top: 3px;">
                        <span class="pix-text" style="font-weight: 800; color: #166534;">Valor: R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            @if(isset($pixPayload))
            <div class="pix-copia-box">{{ trim($pixPayload) }}</div>
            @endif
        </div>
        @endif

    </div>

    @if($orcamento->observacoes)
    <div style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 10px; color:#555; font-size:10px; text-align: justify;">
        <strong>OBSERVAÇÕES:</strong><br>
        {!! nl2br(e(str_replace(['[cite', ']'], '', $orcamento->observacoes))) !!}
    </div>
    @endif

    <div class="footer">
        <div class="validade-msg">Validade: Orçamento e QR Code PIX válidos por 7 dias a partir da emissão.</div>
        Este documento não representa um contrato firmado. Após a aprovação do orçamento, será gerada uma Ordem de Serviço oficial.<br>
        Documento gerado em {{ now()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (Horário de Brasília)
    </div>

</body>
</html>
