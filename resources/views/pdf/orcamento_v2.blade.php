<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $orcamento->numero }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 40px 50px 70px 50px; font-size: 10px; color: #1e293b; line-height: 1.3; }
        
        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        /* HEADER */
        .header-table { margin-bottom: 25px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .logo-img { max-height: 80px; display: block; }
        .orc-number { font-size: 18px; color: #0f172a; font-weight: 800; }
        
        /* CARDS E SEÇÕES */
        .section-bar {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            font-size: 10px;
            padding: 6px 10px;
            border-left: 4px solid #0ea5e9;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
        }
        
        /* TABELA ITENS */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items-table th { background: #f8fafc; text-align: left; padding: 6px; font-size: 9px; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .group-header { color: #0284c7; font-weight: bold; font-size: 10px; margin-top: 10px; display: block; }

        /* --- DASHBOARD FINANCEIRO --- */
        .totals-card {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        /* LINHA AZUL (TOTAL REAL) */
        .row-total-full { border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px; margin-bottom: 8px; }
        .lbl-blue { font-size: 11px; font-weight: bold; color: #0369a1; }
        .val-blue { font-size: 16px; font-weight: 900; color: #0284c7; float: right; }
        
        /* LINHA VERDE (A VISTA) */
        .row-total-pix { margin-top: 5px; }
        .lbl-green { font-size: 10px; font-weight: bold; color: #15803d; }
        .val-green { font-size: 14px; font-weight: 800; color: #16a34a; float: right; }
        .badge-off { background:#dcfce7; color:#166534; padding:2px 5px; border-radius:3px; font-size:8px; margin-left:5px; }

        /* GRID DE PAGAMENTO (CARTÃO vs PIX) */
        .pay-grid { width: 100%; border-spacing: 10px 0; border-collapse: separate; margin-left: -10px; }
        .pay-col { 
            width: 50%; 
            background: #fff; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; 
            vertical-align: top; 
            overflow: hidden;
        }
        .pay-header { 
            background: #f8fafc; 
            padding: 6px; 
            font-size: 9px; 
            font-weight: bold; 
            color: #475569; 
            text-align: center; 
            border-bottom: 1px solid #e2e8f0; 
            text-transform: uppercase;
        }
        
        /* PIX ESPECIFICO */
        .pix-content { padding: 10px; text-align: center; }
        .qr-img { width: 95px; height: 95px; margin-bottom: 8px; }
        .payload-box {
            background: #f1f5f9;
            border: 1px dashed #94a3b8;
            padding: 5px;
            font-family: monospace;
            font-size: 7px;
            color: #334155;
            word-break: break-all !important; /* OBRIGATÓRIO */
            border-radius: 3px;
            text-align: left;
            margin-bottom: 5px;
        }

        /* FOOTER */
        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; font-size: 8px; color: #94a3b8;
            border-top: 1px solid #e2e8f0; padding-top: 10px; background: #fff; line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="footer">
        <strong style="color:#f97316">Validade: 7 dias a partir da emissão.</strong> Este documento não representa contrato firmado.<br>
        Após aprovação, será gerada Ordem de Serviço. Emissão: {{ $dataHoraGeracao }} (Brasília)
    </div>

    <table class="header-table w-full">
        <tr>
            <td width="60%" valign="top">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img">
                @else
                    <h2 style="color:#0ea5e9; margin:0;">{{ $config['empresa_nome'] ?? 'STOFGARD' }}</h2>
                @endif
                <div style="color:#64748b; font-size:9px; margin-top:5px;">
                    CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>
                    {{ $config['empresa_telefone'] ?? '' }}
                </div>
            </td>
            <td width="40%" class="text-right" valign="bottom">
                <div style="font-size:9px; color:#64748b;">ORÇAMENTO Nº</div>
                <div class="orc-number">{{ $orcamento->numero }}</div>
                <div style="margin-top:5px; font-weight:bold; font-size:11px;">{{ strtoupper($orcamento->cliente->nome) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-bar">CLIENTE</div>
    <table class="w-full">
        <tr>
            <td width="60%"><strong>Nome:</strong> {{ $orcamento->cliente->nome }}</td>
            <td width="40%"><strong>Tel:</strong> {{ $orcamento->cliente->telefone }}</td>
        </tr>
        <tr>
            <td><strong>Email:</strong> {{ $orcamento->cliente->email }}</td>
            <td><strong>Local:</strong> {{ $orcamento->cliente->cidade }}/{{ $orcamento->cliente->estado }}</td>
        </tr>
    </table>

    <div class="section-bar">SERVIÇOS</div>
    @foreach(['higienizacao' => 'HIGIENIZAÇÃO', 'impermeabilizacao' => 'IMPERMEABILIZAÇÃO'] as $tipo => $label)
        @php $itensTipo = $orcamento->itens->filter(fn($i) => $i->servico_tipo === $tipo); @endphp
        @if($itensTipo->isNotEmpty())
            <span class="group-header">{{ $label }}</span>
            <table class="items-table">
                <thead><tr><th width="60%">DESCRIÇÃO</th><th width="10%">QTD</th><th width="15%" class="text-right">UNIT</th><th width="15%" class="text-right">TOTAL</th></tr></thead>
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

    <div class="section-bar">RESUMO FINANCEIRO</div>

    <div class="totals-card">
        <div class="row-total-full">
            <span class="lbl-blue">VALOR TOTAL</span>
            <span class="val-blue">R$ {{ number_format($total, 2, ',', '.') }}</span>
            <div style="clear:both"></div>
        </div>
        
        <div class="row-total-pix">
            <span class="lbl-green">
                À VISTA (PIX/DINHEIRO) 
                <span class="badge-off">{{ $percDesconto }}% OFF</span>
            </span>
            <span class="val-green">R$ {{ number_format($totalAvista, 2, ',', '.') }}</span>
            <div style="clear:both"></div>
            <div style="font-size:8px; color:#64748b; margin-top:3px;">
                *Desconto por liberalidade para pagamento imediato.
            </div>
        </div>
    </div>

    <table class="pay-grid">
        <tr>
            <td class="pay-col">
                <div class="pay-header">CARTÃO DE CRÉDITO</div>
                <div style="padding:10px;">
                    @if(count($regras) > 0)
                        @foreach($regras as $r)
                        <div style="font-size:9px; border-bottom:1px dashed #f1f5f9; padding:4px 0; display:flex; justify-content:space-between;">
                            <span><strong>{{ $r['parcelas'] }}x</strong> R$ {{ number_format(($total * (1 + ($r['taxa']/100))) / $r['parcelas'], 2, ',', '.') }}</span>
                            <span style="color:#94a3b8;">Total: {{ number_format($total * (1 + ($r['taxa']/100)), 2, ',', '.') }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center" style="color:#ccc; padding:10px;">Consulte parcelamento.</div>
                    @endif
                </div>
            </td>

            <td class="pay-col">
                <div class="pay-header" style="background:#f0fdf4; color:#166534;">PAGAMENTO PIX</div>
                <div class="pix-content">
                    @if($pix['ativo'])
                        @if($pix['img'])
                            <img src="{{ $pix['img'] }}" class="qr-img">
                        @else
                            <div style="color:#ccc; padding:20px;">QR Indisponível</div>
                        @endif

                        <div style="text-align:left; font-size:8px; font-weight:bold; color:#334155; margin-bottom:2px;">
                            COPIA E COLA:
                        </div>
                        <div class="payload-box">
                            {{ $pix['payload'] ?? 'Erro no payload' }}
                        </div>
                        
                        <div style="font-size:8px; color:#64748b; text-align:left;">
                            <strong>Fav:</strong> {{ substr($pix['beneficiario'], 0, 18) }}<br>
                            <strong>Ref:</strong> {{ $pix['txid'] }}
                        </div>
                    @else
                        <div style="padding:20px; color:#ccc;">Opção desativada.</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>