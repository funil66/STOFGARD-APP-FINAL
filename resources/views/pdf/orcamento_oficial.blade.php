<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $orcamento->numero }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 40px 50px 60px 50px; font-size: 10px; color: #1e293b; line-height: 1.3; }
        
        /* UTILITARIOS */
        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-blue { color: #0ea5e9; }
        .text-slate { color: #64748b; }
        
        /* HEADER */
        .header-table { margin-bottom: 25px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .logo-img { max-height: 70px; display: block; }
        .orc-number { font-size: 18px; color: #0f172a; font-weight: 800; }
        
        /* SEÇÕES */
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
        
        /* TABELA DE ITENS */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items-table th { background: #f8fafc; text-align: left; padding: 6px; font-size: 9px; color: #475569; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .group-header { color: #0284c7; font-weight: bold; font-size: 10px; margin-top: 10px; display: block; }

        /* --- NOVO LAYOUT FINANCEIRO (GRID DE 3 COLUNAS SIMULADO) --- */
        .finance-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
        }
        .finance-grid td { vertical-align: top; padding: 15px; }
        
        /* COLUNA 1: TOTAIS */
        .col-summary { width: 30%; background-color: #f8fafc; border-right: 1px dashed #e2e8f0; }
        .summary-row { margin-bottom: 8px; }
        .summary-label { display: block; font-size: 9px; color: #64748b; }
        .summary-value { display: block; font-size: 12px; font-weight: bold; color: #334155; }
        .summary-total { margin-top: 10px; padding-top: 10px; border-top: 1px solid #cbd5e1; }
        .big-price { font-size: 16px; color: #16a34a; font-weight: 900; }
        
        /* COLUNA 2: PARCELAMENTO */
        .col-installments { width: 35%; border-right: 1px dashed #e2e8f0; }
        .inst-table { width: 100%; font-size: 9px; }
        .inst-table td { padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
        
        /* COLUNA 3: PIX (O FIX) */
        .col-pix { width: 35%; background-color: #f0fdf4; text-align: center; }
        
        .qr-wrapper {
            background: #fff;
            padding: 5px;
            display: inline-block;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .qr-img { width: 100px; height: 100px; display: block; }
        
        /* A CORREÇÃO DO COPIA E COLA */
        .payload-container {
            margin-top: 10px;
            text-align: left;
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
            font-family: 'Courier New', monospace; /* Fonte monoespaçada ajuda na quebra */
            font-size: 8px;
            padding: 6px;
            border-radius: 3px;
            width: 100%; 
            
            /* AS REGRAS MÁGICAS PARA QUEBRAR O TEXTO */
            white-space: pre-wrap;      /* Mantém quebras se houver, mas quebra se precisar */
            word-wrap: break-word;      /* Quebra palavras longas */
            word-break: break-all;      /* Quebra em qualquer caractere se precisar */
            overflow-wrap: break-word;  /* Padrão moderno */
            display: block;
        }

        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            height: 30px; text-align: center; font-size: 9px; color: #94a3b8;
            border-top: 1px solid #e2e8f0; padding-top: 10px; background: #fff;
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
                    <h2 class="text-blue" style="margin:0;">{{ $config['empresa_nome'] ?? 'STOFGARD' }}</h2>
                @endif
                <div class="text-slate" style="font-size:9px; margin-top:5px;">
                    {{ $config['empresa_nome'] ?? 'Stofgard' }}<br>
                    CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>
                    {{ $config['empresa_telefone'] ?? '' }}
                </div>
            </td>
            <td width="40%" class="text-right" valign="bottom">
                <div class="text-slate" style="font-size:9px;">ORÇAMENTO</div>
                <div class="orc-number">{{ $orcamento->numero }}</div>
                <div style="margin-top:5px; font-weight:bold; font-size:11px;">{{ strtoupper($orcamento->cliente->nome) }}</div>
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
    
    <table class="finance-grid">
        <tr>
            <td class="col-summary">
                <div class="summary-row">
                    <span class="summary-label">SUBTOTAL</span>
                    <span class="summary-value">R$ {{ number_format($total, 2, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">DESCONTO PIX ({{ $percDesconto }}%)</span>
                    <span class="summary-value text-blue">- R$ {{ number_format($total - $totalAvista, 2, ',', '.') }}</span>
                </div>
                <div class="summary-total">
                    <span class="summary-label">TOTAL À VISTA</span>
                    <span class="big-price">R$ {{ number_format($totalAvista, 2, ',', '.') }}</span>
                </div>
            </td>

            <td class="col-installments">
                <div style="font-weight:bold; font-size:10px; margin-bottom:5px; color:#334155;">NO CARTÃO:</div>
                @if(count($regras) > 0)
                    <table class="inst-table">
                    @foreach($regras as $r)
                        <tr>
                            <td><strong>{{ $r['parcelas'] }}x</strong> R$ {{ number_format(($total * (1 + ($r['taxa']/100))) / $r['parcelas'], 2, ',', '.') }}</td>
                            <td class="text-right text-slate">Total: {{ number_format($total * (1 + ($r['taxa']/100)), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </table>
                @else
                    <span class="text-slate" style="font-size:9px;">Consulte condições.</span>
                @endif
            </td>

            <td class="col-pix">
                @if($pix['ativo'])
                    <div style="font-weight:bold; color:#166534; margin-bottom:5px; font-size:11px;">PAGUE COM PIX</div>
                    
                    @if($pix['img'])
                        <div class="qr-wrapper">
                            <img src="{{ $pix['img'] }}" class="qr-img">
                        </div>
                    @else
                        <div style="color:#ccc; font-size:9px; padding:20px;">QR OFF</div>
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