<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $orcamento->numero }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 40px 50px 60px 50px; font-size: 10px; color: #1e293b; line-height: 1.3; }
        
        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-slate { color: #64748b; }
        
        /* HEADER */
        .header-table { margin-bottom: 20px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .logo-img { max-height: 75px; display: block; }
        .orc-number { font-size: 18px; color: #0f172a; font-weight: 800; }
        
        /* SEÇÕES */
        .section-bar {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            font-size: 9px;
            padding: 5px 10px;
            border-left: 4px solid #0ea5e9;
            margin: 15px 0 10px 0;
            text-transform: uppercase;
        }
        
        /* ITENS */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items-table th { background: #f8fafc; text-align: left; padding: 5px; font-size: 8px; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 6px 5px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
        .group-header { color: #0284c7; font-weight: bold; font-size: 9px; margin-top: 8px; display: block; }

        /* --- LAYOUT FINANCEIRO NOVO --- */
        .finance-layout { width: 100%; border-collapse: separate; border-spacing: 15px 0; margin-left: -15px; margin-top: 10px; }
        .col-left { width: 65%; vertical-align: top; }
        .col-right { width: 35%; vertical-align: top; }

        /* Esquerda: Totais e Cartão */
        .totals-box {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .row-total { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 5px; }
        .lbl-blue { font-size: 10px; font-weight: bold; color: #0369a1; }
        .val-blue { font-size: 14px; font-weight: 900; color: #0284c7; }
        
        .row-promo { display: flex; justify-content: space-between; align-items: flex-end; background-color: #f0fdf4; padding: 5px; border-radius: 4px; }
        .lbl-green { font-size: 10px; font-weight: bold; color: #15803d; }
        .val-green { font-size: 14px; font-weight: 800; color: #16a34a; }
        
        .cards-list { margin-top: 10px; }
        .card-title { font-size: 9px; font-weight: bold; color: #334155; margin-bottom: 5px; border-bottom: 1px solid #f1f5f9; padding-bottom: 2px; }
        .inst-row { font-size: 9px; color: #475569; display: flex; justify-content: space-between; padding: 2px 0; }

        /* Direita: PIX Compacto */
        .pix-card {
            background-color: #fff;
            border: 1px solid #16a34a;
            border-radius: 6px;
            padding: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .pix-title { 
            color: #16a34a; font-weight: 800; font-size: 10px; text-align: center; 
            text-transform: uppercase; border-bottom: 1px dashed #bbf7d0; padding-bottom: 4px; margin-bottom: 8px;
        }
        
        .pix-inner-table { width: 100%; margin-bottom: 8px; }
        .qr-cell { width: 75px; vertical-align: middle; }
        .qr-img { width: 75px; height: 75px; display: block; border: 2px solid #f0fdf4; border-radius: 4px; }
        
        .info-cell { vertical-align: middle; padding-left: 8px; }
        .pix-label { font-size: 7px; color: #64748b; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 1px; }
        .pix-val { font-size: 8px; color: #0f172a; font-weight: bold; display: block; margin-bottom: 4px; word-wrap: break-word; }

        .payload-label { font-size: 7px; font-weight: bold; color: #16a34a; margin-bottom: 2px; }
        .payload-box {
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            padding: 4px;
            font-family: 'Courier New', monospace;
            font-size: 6px; /* Fonte pequena para caber */
            color: #334155;
            word-break: break-all !important;
            border-radius: 3px;
            line-height: 1.1;
            text-align: justify;
        }

        /* FOOTER ONE-LINER */
        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; font-size: 7px; color: #94a3b8;
            border-top: 1px solid #e2e8f0; padding: 8px 30px; background: #fff;
            white-space: nowrap; overflow: hidden;
        }
    </style>
</head>
<body>

    <div class="footer">
        <strong style="color:#f97316">Validade: 7 dias.</strong> Este documento não é contrato. Após aprovação, gera-se OS. 
        <span style="color:#cbd5e1; margin: 0 5px;">•</span> 
        Emissão: {{ $dataHoraGeracao }} (Brasília)
    </div>

    <table class="header-table w-full">
        <tr>
            <td width="60%" valign="top">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img">
                @else
                    <h2 style="color:#0ea5e9; margin:0;">{{ $config['empresa_nome'] ?? 'STOFGARD' }}</h2>
                @endif
                <div class="text-slate" style="font-size:9px; margin-top:5px;">
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

    <div class="section-bar">DADOS DO CLIENTE</div>
    <table class="w-full">
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

    <div class="section-bar">RESUMO & PAGAMENTO</div>
    
    <table class="finance-layout">
        <tr>
            <td class="col-left">
                <div class="totals-box">
                    <div class="row-total">
                        <span class="lbl-blue">VALOR TOTAL DOS SERVIÇOS</span>
                        <span class="val-blue">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                    <div class="row-promo">
                        <span class="lbl-green">À VISTA (PIX/DINHEIRO) - {{ $percDesconto }}% OFF</span>
                        <span class="val-green">R$ {{ number_format($totalAvista, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="cards-list">
                    <div class="card-title">CONDIÇÕES NO CARTÃO DE CRÉDITO</div>
                    @if(count($regras) > 0)
                        @foreach($regras as $r)
                        <div class="inst-row">
                            <span><strong>{{ $r['parcelas'] }}x</strong> R$ {{ number_format(($total * (1 + ($r['taxa']/100))) / $r['parcelas'], 2, ',', '.') }}</span>
                            <span>Total: {{ number_format($total * (1 + ($r['taxa']/100)), 2, ',', '.') }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center" style="color:#ccc; font-size:9px;">Consulte parcelamento.</div>
                    @endif
                </div>
            </td>

            <td class="col-right">
                @if($pix['ativo'])
                <div class="pix-card">
                    <div class="pix-title">PAGUE COM PIX</div>
                    
                    <table class="pix-inner-table">
                        <tr>
                            <td class="qr-cell">
                                @if($pix['img'])
                                    <img src="{{ $pix['img'] }}" class="qr-img">
                                @else
                                    <div style="width:75px; height:75px; background:#f1f5f9; color:#ccc; font-size:8px; line-height:75px; text-align:center;">Sem QR</div>
                                @endif
                            </td>
                            <td class="info-cell">
                                <span class="pix-label">Chave Usada:</span>
                                <span class="pix-val">{{ $pix['chave_visual'] }}</span>
                                
                                <span class="pix-label" style="margin-top:3px;">Beneficiário:</span>
                                <span class="pix-val">{{ substr($pix['beneficiario'], 0, 15) }}</span>
                            </td>
                        </tr>
                    </table>

                    <div class="payload-label">COPIA E COLA:</div>
                    <div class="payload-box">
                        {{ $pix['payload'] ?? 'Erro na geração da chave.' }}
                    </div>
                </div>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>