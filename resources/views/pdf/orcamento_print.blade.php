<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Orçamento #{{ $orcamento->numero_orcamento }}</title>
    <style>
        @page { margin: 120px 30px 80px 30px; } /* Margem para cabeçalho e rodapé fixos */
        
        body { font-family: Helvetica, sans-serif; font-size: 10px; color: #333; }
        
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        
        /* CORES */
        .bg-blue { background-color: #0f2c4f; color: white; }
        .bg-orange { background-color: #d97706; color: white; }
        .text-blue { color: #0f2c4f; }
        .text-orange { color: #d97706; }
        
        /* 1. CABEÇALHO (FIXO) */
        header { position: fixed; top: -100px; left: 0; right: 0; height: 100px; }
        .logo { width: 180px; } /* Aumentado */
        
        .header-box {
            background-color: #0f2c4f;
            color: white;
            width: 200px;
            float: right;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .header-item { margin-bottom: 5px; }
        .header-label { font-size: 8px; opacity: 0.8; text-transform: uppercase; }
        .header-value { font-size: 11px; font-weight: bold; }
        
        /* 2. DADOS CLIENTE */
        .client-section { margin-top: 20px; border-bottom: 2px solid #0f2c4f; padding-bottom: 10px; }
        .label { font-weight: bold; color: #0f2c4f; margin-right: 5px; }
        
        /* 3. ITENS */
        .cat-header { margin-top: 20px; margin-bottom: 5px; }
        .cat-badge { 
            display: inline-block; 
            padding: 6px 15px; 
            border-radius: 20px; 
            color: white; 
            font-weight: bold; 
            font-size: 11px; 
            text-transform: uppercase;
        }
        .desc-box { font-size: 9px; color: #555; margin-left: 5px; font-style: italic; margin-bottom: 5px; display: block; }
        
        .item-table td { padding: 6px; border-bottom: 1px solid #eee; }
        .item-name { font-size: 11px; font-weight: bold; }
        
        /* 4. VALORES E PIX */
        .values-section { margin-top: 30px; page-break-inside: avoid; }
        
        /* Pix Verde */
        .pix-container {
            width: 55%;
            float: left;
            background-color: #f0fdf4; /* Verde Claro */
            border: 1px solid #16a34a; /* Verde Escuro */
            border-radius: 6px;
            padding: 10px;
        }
        .pix-title { color: #16a34a; font-weight: bold; font-size: 11px; margin-bottom: 8px; }
        
        /* Totais */
        .totals-container { width: 40%; float: right; }
        .totals-table td { text-align: right; padding: 4px; }
        .total-final { 
            font-size: 14px; font-weight: bold; color: #0f2c4f; 
            border-top: 2px solid #0f2c4f; padding-top: 5px; margin-top: 5px;
        }
        
        /* 5. RODAPÉ (FIXO) */
        footer { position: fixed; bottom: -60px; left: 0; right: 0; height: 60px; text-align: center; }
        .validade-msg { color: #d97706; font-weight: bold; font-size: 10px; margin-bottom: 5px; }
        .legal-msg { font-size: 8px; color: #777; }
    </style>
</head>
<body>

    <header>
        <table width="100%">
            <tr>
                <td width="60%">
                    @if(file_exists(public_path('images/logo-stofgard.png')))
                        <img src="{{ public_path('images/logo-stofgard.png') }}" class="logo">
                    @else
                        <h1 class="text-blue">STOFGARD</h1>
                    @endif
                </td>
                <td width="40%" align="right">
                    <div class="header-box">
                        <div class="header-item">
                            <div class="header-label">Orçamento Nº</div>
                            <div class="header-value">#{{ $orcamento->numero_orcamento }}</div>
                        </div>
                        <div class="header-item">
                            <div class="header-label">Data de Emissão</div>
                            <div class="header-value">{{ $orcamento->data_orcamento ? \Carbon\Carbon::parse($orcamento->data_orcamento)->format('d/m/Y') : date('d/m/Y') }}</div>
                        </div>
                        <div class="header-item" style="margin-bottom:0">
                            <div class="header-label">Validade</div>
                            <div class="header-value">{{ $orcamento->data_validade ? \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') : '7 dias' }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <div class="validade-msg">Validade: Orçamento e QR Code PIX válidos por 7 dias a partir da emissão.</div>
        <div class="legal-msg">
            Este documento não representa um contrato firmado. Após a aprovação do orçamento, será gerada uma Ordem de Serviço oficial.<br>
            Documento gerado em {{ date('d/m/Y H:i') }}
        </div>
    </footer>

    <main>
        <div class="client-section">
            <table width="100%">
                <tr>
                    <td width="60%">
                        <span class="label">CLIENTE:</span> {{ $orcamento->cliente->nome }}
                    </td>
                    @if(!empty($orcamento->cliente->telefone) || !empty($orcamento->cliente->celular))
                    <td width="40%" class="text-right">
                        <span class="label">TEL:</span> {{ $orcamento->cliente->telefone ?? $orcamento->cliente->celular }}
                    </td>
                    @endif
                </tr>
                @if(!empty($orcamento->cliente->email) || !empty($orcamento->cliente->endereco))
                <tr>
                    @if(!empty($orcamento->cliente->email))
                    <td><span class="label">EMAIL:</span> {{ $orcamento->cliente->email }}</td>
                    @else <td></td> @endif
                    
                    @if(!empty($orcamento->cliente->endereco))
                    <td class="text-right"><span class="label">ENDEREÇO:</span> {{ $orcamento->cliente->endereco }}</td>
                    @endif
                </tr>
                @endif
            </table>
        </div>

        @php
            $higi = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && stripos($i->tabelaPreco->tipo_servico, 'higienizacao') !== false);
            $imper = $orcamento->itens->filter(fn($i) => $i->tabelaPreco && stripos($i->tabelaPreco->tipo_servico, 'impermeabilizacao') !== false);
        @endphp

        @if($higi->count() > 0)
        <div style="margin-bottom: 20px;">
            <div class="cat-header">
                <span class="cat-badge bg-blue">HIGIENIZAÇÃO</span>
            </div>
            <span class="desc-box">Biossanitização Profunda: Extração de alta pressão para eliminação de biofilmes, ácaros e bactérias, garantindo assepsia total das fibras e neutralização de odores.</span>
            
            <table class="item-table">
                @foreach($higi as $item)
                <tr>
                    <td width="60%">
                        <div class="item-name">{{ str_replace(['[cite', ']'], '', $item->nome_item) }}</div>
                    </td>
                    <td width="10%" align="center">{{ $item->unidade_medida ?? 'UN' }}</td>
                    <td width="15%" align="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td width="15%" align="right"><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        @if($imper->count() > 0)
        <div style="margin-bottom: 20px;">
            <div class="cat-header">
                <span class="cat-badge bg-orange">IMPERMEABILIZAÇÃO</span>
            </div>
            <span class="desc-box">Escudo hidrofóbico invisível que repele líquidos e óleos, preservando a integridade das fibras e prolongando a vida útil do tecido.</span>
            
            <table class="item-table">
                @foreach($imper as $item)
                <tr>
                    <td width="60%">
                        <div class="item-name">{{ str_replace(['[cite', ']'], '', $item->nome_item) }}</div>
                    </td>
                    <td width="10%" align="center">{{ $item->unidade_medida ?? 'UN' }}</td>
                    <td width="15%" align="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td width="15%" align="right"><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        <div class="values-section">
            <table width="100%">
                <tr>
                    <td width="55%" style="padding-right: 15px;">
                        @if(isset($qrCodePix))
                        <div class="pix-container">
                            <div class="pix-title">PAGAMENTO VIA PIX</div>
                            <table width="100%">
                                <tr>
                                    <td width="80"><img src="{{ $qrCodePix }}" style="width: 70px; height: 70px;"></td>
                                    <td style="padding-left: 10px; font-size: 10px;">
                                        <strong>Chave:</strong> {{ $chavePix }}<br>
                                        <strong>Valor:</strong> R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}<br>
                                        @if(isset($pixPayload))
                                        <div style="margin-top:5px; font-family:monospace; font-size:8px; word-break:break-all; background:white; padding:2px; border:1px dashed #ccc;">
                                            {{ substr($pixPayload, 0, 40) }}...
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @endif
                    </td>

                    <td width="45%">
                        <table class="totals-table">
                            <tr>
                                <td>Valor Base:</td>
                                <td>R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</td>
                            </tr>
                            @if($orcamento->valor_desconto > 0)
                            <tr>
                                <td class="text-orange">Descontos:</td>
                                <td class="text-orange">- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($orcamento->acrescimo > 0)
                            <tr>
                                <td>Comissão:</td>
                                <td>+ R$ {{ number_format($orcamento->acrescimo, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td>Forma de Pagamento:</td>
                                <td><strong>{{ ucfirst($orcamento->forma_pagamento ?? 'À vista') }}</strong></td>
                            </tr>
                        </table>
                        <div class="text-right total-final">
                            TOTAL: R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </main>
</body>
</html>
