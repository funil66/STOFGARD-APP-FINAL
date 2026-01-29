<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $orcamento->numero }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 40px 50px 60px 50px; font-size: 10px; color: #334155; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .header-table { margin-bottom: 25px; border: none; width: 100%; }
        .logo-cell { width: 60%; vertical-align: top; }
        .meta-cell { width: 40%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: right; }
        .logo-img { max-height: 70px; margin-bottom: 8px; display: block; }
        .orc-number { font-size: 16px; font-weight: 800; color: #0284c7; display: block; margin-bottom: 5px; }
        .section-header { background-color: #f1f5f9; color: #0f172a; font-weight: 800; font-size: 11px; text-transform: uppercase; padding: 6px 10px; border-left: 4px solid #0284c7; margin-top: 20px; margin-bottom: 10px; border-radius: 0 4px 4px 0; }
        .items-table { width: 100%; margin-bottom: 10px; }
        .items-table th { background: #f1f5f9; text-align: left; padding: 6px; border-bottom: 2px solid #cbd5e1; font-weight: bold; font-size: 9px; }
        .items-table td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .finance-table { width: 100%; margin-top: 10px; border: none; }
        .finance-left { width: 60%; vertical-align: top; padding-right: 20px; }
        .finance-right { width: 40%; vertical-align: top; }
        .condicao-destaque { background-color: #f0fdf4; color: #166534; font-weight: bold; padding: 8px; border-radius: 4px; margin-bottom: 10px; border: 1px solid #bbf7d0; }
        .condicao-row { padding: 4px 0; border-bottom: 1px dashed #e2e8f0; font-size: 10px; color: #334155; }
        .pix-box { border: 2px solid #16a34a; background-color: #f0fdf4; border-radius: 8px; padding: 10px; text-align: center; }
        .qr-img { width: 120px; height: 120px; margin: 5px auto; display: block; background: white; padding: 5px; border-radius: 4px; }
        .pix-info { background: #fff; padding: 6px; border: 1px dashed #16a34a; border-radius: 4px; margin-top: 8px; font-family: monospace; font-size: 9px; color: #15803d; text-align: left; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 30px; background: #fff; border-top: 1px solid #e2e8f0; text-align: center; padding-top: 10px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="footer">
        Orçamento válido até <strong style="color:#f97316">{{ \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') }}</strong>. 
        Documento gerado em {{ now()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
    </div>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(!empty($config['empresa_logo']) && file_exists(public_path('storage/' . $config['empresa_logo'])))
                    <img src="{{ asset('storage/' . $config['empresa_logo']) }}" class="logo-img">
                @else
                    <h2 style="margin:0; color:#0284c7;">{{ $config['empresa_nome'] ?? 'STOFGARD' }}</h2>
                @endif
                <div style="color:#64748b; font-size:10px; margin-top:5px;">
                    CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>
                    {{ $config['empresa_telefone'] ?? '' }} | {{ $config['empresa_email'] ?? '' }}
                </div>
            </td>
            <td class="meta-cell">
                <span style="font-size:10px; font-weight:bold; color:#64748b;">ORÇAMENTO Nº</span>
                <span class="orc-number">{{ $orcamento->numero }}</span>
                <div style="font-size:10px;">Emissão: <strong>{{ \Carbon\Carbon::parse($orcamento->created_at)->format('d/m/Y H:i') }}</strong></div>
                <div style="margin-top:8px; padding-top:5px; border-top:1px dashed #cbd5e1; color:#0f172a; font-weight:bold; font-size:11px;">
                    {{ strtoupper($orcamento->cliente->nome) }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section-header">DADOS DO CLIENTE</div>
    <table style="width:100%; margin-bottom:15px;">
        <tr>
            <td width="60%"><strong>Nome:</strong> {{ $orcamento->cliente->nome }}</td>
            <td width="40%"><strong>Telefone:</strong> {{ $orcamento->cliente->telefone }}</td>
        </tr>
        <tr>
            <td><strong>Email:</strong> {{ $orcamento->cliente->email }}</td>
            <td><strong>Local:</strong> {{ $orcamento->cliente->cidade }}/{{ $orcamento->cliente->estado }}</td>
        </tr>
    </table>

    <div class="section-header">ITENS E SERVIÇOS</div>
    @foreach(['higienizacao' => 'HIGIENIZAÇÃO', 'impermeabilizacao' => 'IMPERMEABILIZAÇÃO'] as $tipo => $label)
        @php $itensTipo = $orcamento->itens->filter(fn($i) => $i->servico_tipo === $tipo); @endphp
        @if($itensTipo->isNotEmpty())
            <div style="margin-top:10px; font-weight:bold; color:#0284c7; font-size:10px; border-bottom:1px solid #bae6fd; display:inline-block; padding-bottom:2px;">{{ $label }}</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="50%">Descrição</th>
                        <th width="10%">Un.</th>
                        <th width="10%">Qtd.</th>
                        <th width="15%" align="right">Unit.</th>
                        <th width="15%" align="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itensTipo as $item)
                    <tr>
                        <td>{{ $item->item_nome }}</td>
                        <td>{{ strtoupper($item->unidade) }}</td>
                        <td>{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                        <td align="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td align="right"><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="section-header">VALORES E PAGAMENTO</div>
    <table class="finance-table">
        <tr>
            <td class="finance-left">
                <div class="condicao-destaque">
                    <table width="100%">
                        <tr>
                            <td>À VISTA (PIX/DINHEIRO)</td>
                            <td align="right">R$ {{ number_format($totalAvista, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-size:9px; font-weight:normal;">Desconto de {{ $percDesconto }}% aplicado</td>
                        </tr>
                    </table>
                </div>
                
                @if(count($regras) > 0)
                    <div style="font-weight:bold; margin-bottom:5px; font-size:10px;">Parcelamento Cartão:</div>
                    @foreach($regras as $regra)
                        @php 
                            $p = (int)$regra['parcelas'];
                            $taxa = (float)$regra['taxa'];
                            $totalParc = $total * (1 + ($taxa/100));
                            $valParc = $totalParc / $p;
                        @endphp
                        <div class="condicao-row">
                            <span style="font-weight:bold;">{{ $p }}x</span> de R$ {{ number_format($valParc, 2, ',', '.') }}
                            <span style="float:right; color:#64748b;">Total: R$ {{ number_format($totalParc, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                @endif
            </td>

            <td class="finance-right">
                @if($shouldShowPix)
                <div class="pix-box">
                    <strong style="color:#15803d; font-size:11px;">PAGUE COM PIX</strong>
                    @if($qrCodeImg)
                        <img src="{{ $qrCodeImg }}" class="qr-img">
                        <div class="pix-info">
                            <strong>Favorecido:</strong> {{ $pixBeneficiario }}<br>
                            <strong>Chave:</strong> {{ $pixKey }}
                        </div>
                    @else
                        <div style="height:100px; padding-top:40px; color:#ccc; font-size:9px;">QR Indisponível</div>
                    @endif
                </div>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>