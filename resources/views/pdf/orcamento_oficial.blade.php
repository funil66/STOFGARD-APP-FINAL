<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orçamento {{ $orcamento->numero }}</title>
    <style>
        @page { margin: 0cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0cm;
            padding: 40px 50px 80px 50px;
            font-size: 10px;
            color: #334155;
            line-height: 1.5;
        }
        table { width: 100%; border-collapse: collapse; }
        .header-table { margin-bottom: 30px; }
        .logo-cell { width: 55%; vertical-align: top; padding-right: 20px; }
        .meta-cell { 
            width: 45%; vertical-align: top; 
            background-color: #f0f9ff; border: 1px solid #bae6fd; 
            border-radius: 8px; padding: 15px 20px; text-align: right; 
        }
        .logo-img { max-height: 80px; margin-bottom: 10px; display: block; }
        .company-info { font-size: 9px; color: #64748b; line-height: 1.4; }
        .orc-label { font-size: 9px; font-weight: bold; color: #0ea5e9; text-transform: uppercase; letter-spacing: 1px; }
        .orc-value { font-size: 20px; font-weight: 900; color: #0369a1; margin-bottom: 8px; display: block; }
        .section-title {
            font-size: 11px; font-weight: 900; color: #0f172a; text-transform: uppercase;
            border-left: 5px solid #0369a1; 
            padding: 8px 15px; 
            margin-top: 30px; margin-bottom: 15px;
            background-color: #f1f5f9; 
            border-radius: 0 4px 4px 0;
        }
        .items-table th { background: #f1f5f9; text-align: left; font-size: 9px; padding: 8px 5px; border-bottom: 2px solid #cbd5e1; }
        .items-table td { padding: 10px 5px; font-size: 10px; border-bottom: 1px solid #f1f5f9; }
        .tag-blue { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 9px; border: 1px solid #bae6fd; display:inline-block; margin-bottom:5px; }
        .tag-yellow { background: #fef9c3; color: #b45309; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 9px; border: 1px solid #fde047; display:inline-block; margin-bottom:5px; }
        .col-values { width: 60%; vertical-align: top; padding-right: 25px; }
        .col-pix { width: 40%; vertical-align: top; }
        .values-table { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .values-table th { background: #f8fafc; text-align: left; padding: 10px; font-size: 9px; border-bottom: 1px solid #e2e8f0; }
        .values-table td { padding: 10px; font-size: 10px; border-bottom: 1px solid #f1f5f9; }
        .final-price { font-size: 14px; font-weight: 900; color: #0f172a; }
        .discount-badge { background:#dcfce7; color:#166534; padding:2px 6px; border-radius:4px; font-size:8px; font-weight:bold; margin-left: 5px; }
        .pix-box { 
            border: 2px solid #16a34a; 
            border-radius: 8px; 
            padding: 15px; 
            text-align: center; 
            background-color: #f0fdf4; 
        }
        .qr-img { width: 120px; height: 120px; margin: 10px auto; border: 4px solid #fff; display: block; }
        .pix-data-container { background: #fff; padding: 8px; border-radius: 6px; margin-top: 12px; border: 1px dashed #16a34a; }
        .pix-key-value { 
            font-family: monospace; 
            font-size: 11px; 
            color: #166534; 
            font-weight: bold; 
            word-break: break-all; 
            display: block; 
        }
        .footer { 
            position: fixed; bottom: 20px; left: 0; right: 0; 
            height: 50px; text-align: center; font-size: 9px; color: #94a3b8; 
            border-top: 1px solid #e2e8f0; padding-top: 10px; background-color: #fff;
        }
        .validity-text { color: #f97316; font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>

    @php
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        $regras = $config['financeiro_parcelamento'] ?? [];
        
        $pixKey = trim($orcamento->pix_chave_selecionada);
        
        // Fallback
        if (empty($pixKey)) {
            $rawPixKeys = $config['financeiro_pix_keys'] ?? [];
            if (is_array($rawPixKeys) && !empty($rawPixKeys)) {
                $first = reset($rawPixKeys);
                $pixKey = trim($first['chave'] ?? null);
            }
        }

        // LÓGICA DE TRATAMENTO DE CHAVE (BLINDADA)
        $pixKeyForPayload = $pixKey;
        
        if (!empty($pixKey)) {
            // 1. Verifica se é Chave Aleatória (EVP) - Formato UUID
            $isEVP = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pixKey);
            
            // 2. Verifica se é Email
            $isEmail = filter_var($pixKey, FILTER_VALIDATE_EMAIL);

            if ($isEVP || $isEmail) {
                // Se for Aleatória ou Email, USA EXATAMENTE COMO ESTÁ
                $pixKeyForPayload = $pixKey;
            } else {
                // Se não, assume que é Telefone, CPF ou CNPJ e limpa caracteres
                $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
                
                // Se for Telefone (10 ou 11 dígitos começando com DDD)
                $isPhone = (strlen($onlyNums) == 10 || strlen($onlyNums) == 11);
                
                if ($isPhone && !str_starts_with($pixKey, '+55')) {
                    $pixKeyForPayload = '+55' . $onlyNums;
                } else {
                    $pixKeyForPayload = $onlyNums; // CPF ou CNPJ
                }
            }
        }
        
        $qrCodeImg = null;
        $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);
        $beneficiario = substr($config['empresa_nome'] ?? 'Stofgard', 0, 25);

        if ($shouldShowPix && class_exists('App\Services\PixPayload')) {
            $payload = \App\Services\PixPayload::gerar((string)$pixKeyForPayload, $beneficiario, 'Ribeirao Preto', $orcamento->numero, $totalAvista);
            
            try {
                $qrClass = '\\SimpleSoftwareIO\\QrCode\\Facades\\QrCode';
                if (class_exists($qrClass)) {
                    $pngData = $qrClass::format('png')
                        ->size(200)
                        ->margin(0)
                        ->generate($payload);
                    $qrCodeImg = 'data:image/png;base64,' . base64_encode($pngData);
                }
            } catch (\\Exception $e) {
                // Silencioso
            }
        }
        
        $emissao = \Carbon\Carbon::parse($orcamento->created_at)->setTimezone('America/Sao_Paulo');
        $validade = \Carbon\Carbon::parse($orcamento->data_validade);
    @endphp

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @php $logoPath = public_path('storage/' . ($config['empresa_logo'] ?? 'logos/default.png')); @endphp
                @if(file_exists($logoPath)) <img src="{{ $logoPath }}" class="logo-img"> @endif
                <div class="company-info">CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>{{ $config['empresa_telefone'] ?? '' }}<br>{{ $config['empresa_email'] ?? '' }}</div>
            </td>
            <td class="meta-cell">
                <span class="orc-label">ORÇAMENTO Nº</span>
                <span class="orc-value">{{ $orcamento->numero }}</span>
                <div>EMISSÃO: <strong>{{ $emissao->format('d/m/Y H:i') }}</strong></div>
                <div>VALIDADE: <span class="validade-destaque">{{ $validade->format('d/m/Y') }}</span></div>
                <div style="margin-top:8px; border-top:1px dashed #bae6fd; padding-top:5px; font-weight:bold; color:#0284c7;">{{ strtoupper($orcamento->cliente->nome) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">DADOS DO CLIENTE</div>
    <table style="width:100%; margin-bottom:20px;">
        <tr><td width="60%"><strong>CLIENTE:</strong> {{ strtoupper($orcamento->cliente->nome) }}</td><td width="40%"><strong>CONTATO:</strong> {{ $orcamento->cliente->telefone }}</td></tr>
        <tr><td><strong>EMAIL:</strong> {{ $orcamento->cliente->email }}</td><td><strong>LOCAL:</strong> {{ $orcamento->cliente->cidade }}/{{ $orcamento->cliente->estado }}</td></tr>
    </table>

    <div class="section-title">ITENS DO ORÇAMENTO</div>
    @foreach(['higienizacao' => ['label'=>'HIGIENIZAÇÃO', 'class'=>'tag-blue'], 'impermeabilizacao' => ['label'=>'IMPERMEABILIZAÇÃO', 'class'=>'tag-yellow']] as $tipo => $style)
        @php $itens = $orcamento->itens->filter(fn($i) => $i->servico_tipo === $tipo); @endphp
        @if($itens->isNotEmpty())
            <div><span class="{{ $style['class'] }}">{{ $style['label'] }}</span></div>
            <table class="items-table" style="margin-bottom:20px;">
                <thead><tr><th width="50%">DESCRIÇÃO</th><th width="10%">UN.</th><th width="10%">QTD.</th><th width="15%" align="right">UNIT.</th><th width="15%" align="right">TOTAL</th></tr></thead>
                <tbody>
                    @foreach($itens as $item)
                    <tr><td>{{ $item->item_nome }}</td><td>{{ strtoupper($item->unidade) }}</td><td>{{ number_format($item->quantidade, 2, ',', '.') }}</td><td align="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td><td align="right"><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="section-title">VALORES & CONDIÇÕES</div>
    <table style="width:100%; margin-top:10px;">
        <tr>
            <td class="col-values">
                <table class="values-table" width="100%">
                    <thead><tr><th width="60%">CONDIÇÃO</th><th width="40%" align="right">VALOR</th></tr></thead>
                    <tbody>
                        <tr style="background-color:#f0fdf4;">
                            <td><strong style="font-size:11px; color:#15803d;">PIX / DINHEIRO</strong> <span class="discount-badge">{{ $percDesconto }}% OFF</span></td>
                            <td align="right"><div class="final-price" style="color:#16a34a;">R$ {{ number_format($totalAvista, 2, ',', '.') }}</div><div style="text-decoration:line-through; font-size:9px; color:#94a3b8;">R$ {{ number_format($total, 2, ',', '.') }}</div></td>
                        </tr>
                        @foreach($regras as $regra)
                            @php $p=(int)$regra['parcelas']; $taxa=(float)$regra['taxa']; $totalParc=$total*(1+($taxa/100)); $valParc=$totalParc/$p; @endphp
                            <tr><td style="color:#334155; font-size:10px;"><strong>{{ $p }}x</strong> de R$ {{ number_format($valParc, 2, ',', '.') }}</td><td align="right" style="color:#64748b; font-size:10px;">Total: R$ {{ number_format($totalParc, 2, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td class="col-pix">
                @if($shouldShowPix)
                <div class="pix-box">
                    <strong style="color:#15803d; font-size:11px; text-transform:uppercase;">Pague com PIX</strong><br>
                    @if($qrCodeImg) 
                        <img src="{{ $qrCodeImg }}" class="qr-img"> 
                    @else 
                        <div style="height:100px; line-height:100px; color:#ccc; padding-top:40px; font-size:9px;">QR Indisponível</div> 
                    @endif
                    <div style="font-size:14px; font-weight:900; color:#166534; margin-top:5px;">R$ {{ number_format($totalAvista, 2, ',', '.') }}</div>
                    <div class="pix-data-container"><span style="font-size:7px; font-weight:bold; color:#15803d; display:block;">CHAVE PIX:</span><span class="pix-key-value">{{ $pixKey }}</span></div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer"><span class="validity-text">Validade: Orçamento e QR Code válidos por 7 dias.</span><br><span style="color:#cbd5e1;">Documento gerado em {{ now()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}</span></div>
</body>
</html>
        }
        // Se tiver @ (Email), NÃO MEXE!
        elseif (strpos($pixKey, '@') !== false) {
            $pixKeyForPayload = $pixKey;
        }
        // Se for Telefone (apenas números, 10 ou 11 dígitos)
        else {
            $onlyNums = preg_replace('/[^0-9]/', '', $pixKey);
            $isPhone = (strlen($onlyNums) == 10 || strlen($onlyNums) == 11);
            $hasCountryCode = str_starts_with($pixKey, '+55');

            // Se for telefone e não tiver +55, adiciona.
            if ($isPhone && !$hasCountryCode) {
                $pixKeyForPayload = '+55' . $onlyNums;
            } else {
                $pixKeyForPayload = $pixKey;
            }
        }
    } else {
        $pixKeyForPayload = null;
    }
    
    // Só mostra se o toggle estiver ON E se existir uma chave válida
    $shouldShowPix = ($orcamento->pdf_incluir_pix ?? true) && !empty($pixKey);

    if ($shouldShowPix && class_exists('App\Services\PixPayload')) {
            // Usa a chave tratada ($pixKeyForPayload) para o código
            $payload = \App\Services\PixPayload::gerar($pixKeyForPayload, $beneficiario, 'Ribeirao Preto', $orcamento->numero, $totalAvista);
            
            // Gera imagem LOCALMENTE (SVG/PNG)
            try {
                // Define a classe em uma variável string para evitar erro de parser do Blade
                $qrClass = '\SimpleSoftwareIO\QrCode\Facades\QrCode';
                if (class_exists($qrClass)) {
                    $pngData = $qrClass::format('png')
                        ->size(200)
                        ->margin(0)
                        ->generate($payload);

                    $qrCodeImg = 'data:image/png;base64,' . base64_encode($pngData);
                }
            } catch (\Exception $e) {
                // Silencioso
            }
        }
    
    $emissao = \Carbon\Carbon::parse($orcamento->created_at)->setTimezone('America/Sao_Paulo');
    $validade = \Carbon\Carbon::parse($orcamento->data_validade);
@endphp

<table class="header-table">
    <tr>
        <td class="logo-cell">
            @php $logoPath = public_path('storage/' . ($config['empresa_logo'] ?? 'logos/default.png')); @endphp
            @if(file_exists($logoPath)) 
                <img src="{{ $logoPath }}" class="logo-img"> 
            @endif
            
            <div class="company-info">
                CNPJ: {{ $config['empresa_cnpj'] ?? '' }}<br>
                {{ $config['empresa_telefone'] ?? '' }}<br>
                {{ $config['empresa_email'] ?? '' }}
            </div>
        </td>
        <td class="meta-cell">
            <span class="orc-label">ORÇAMENTO Nº</span>
            <span class="orc-value">{{ $orcamento->numero }}</span>
            <div>EMISSÃO: <strong>{{ $emissao->format('d/m/Y H:i') }}</strong></div>
            <div>VALIDADE: <span style="color:#f97316; font-weight:bold;">{{ $validade->format('d/m/Y') }}</span></div>
            <div style="margin-top:8px; border-top:1px dashed #bae6fd; padding-top:5px; font-weight:bold; color:#0284c7; font-size:10px;">
                {{ strtoupper($orcamento->cliente->nome) }}
            </div>
        </td>
    </tr>
</table>

<div class="section-title">DADOS DO CLIENTE</div>
<table style="width:100%; margin-bottom:20px;">
    <tr>
        <td width="60%"><strong>CLIENTE:</strong> {{ strtoupper($orcamento->cliente->nome) }}</td>
        <td width="40%"><strong>CONTATO:</strong> {{ $orcamento->cliente->telefone }}</td>
    </tr>
    <tr>
        <td><strong>EMAIL:</strong> {{ $orcamento->cliente->email }}</td>
        <td><strong>LOCAL:</strong> {{ $orcamento->cliente->cidade }}/{{ $orcamento->cliente->estado }}</td>
    </tr>
</table>

<div class="section-title">ITENS DO ORÇAMENTO</div>
@foreach(['higienizacao' => ['label'=>'HIGIENIZAÇÃO', 'class'=>'tag-blue'], 'impermeabilizacao' => ['label'=>'IMPERMEABILIZAÇÃO', 'class'=>'tag-yellow']] as $tipo => $style)
    @php $itens = $orcamento->itens->filter(fn($i) => $i->servico_tipo === $tipo); @endphp
    @if($itens->isNotEmpty())
        <div><span class="{{ $style['class'] }}">{{ $style['label'] }}</span></div>
        <table class="items-table" style="margin-bottom:20px;">
            <thead>
                <tr>
                    <th width="50%">DESCRIÇÃO</th>
                    <th width="10%">UN.</th>
                    <th width="10%">QTD.</th>
                    <th width="15%" align="right">UNIT.</th>
                    <th width="15%" align="right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itens as $item)
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

<div class="section-title">VALORES & CONDIÇÕES</div>
<table style="width:100%; margin-top:10px;">
    <tr>
        <td class="col-values">
            <table class="values-table" width="100%">
                <thead>
                    <tr>
                        <th width="70%">CONDIÇÃO</th>
                        <th width="30%" align="right">VALOR</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background-color:#f8fafc;">
                        <td style="padding: 12px 8px;">
                            <strong style="font-size:11px; color:#0f172a;">PIX / DINHEIRO</strong> 
                            <span class="discount-badge">{{ $percDesconto }}% OFF</span>
                            <div style="font-size:9px; color:#64748b; margin-top:2px;">Pagamento à vista na execução.</div>
                        </td>
                        <td align="right" style="padding: 12px 8px;">
                            <div class="final-price" style="color:#16a34a;">R$ {{ number_format($totalAvista, 2, ',', '.') }}</div>
                            <div style="text-decoration:line-through; font-size:9px; color:#94a3b8;">R$ {{ number_format($total, 2, ',', '.') }}</div>
                        </td>
                    </tr>
                    
                    @foreach($regras as $regra)
                        @php
                            $p = (int)$regra['parcelas'];
                            $taxa = (float)$regra['taxa'];
                            $totalParc = $total * (1 + ($taxa/100));
                            $valParc = $totalParc / $p;
                        @endphp
                        <tr class="installment-row">
                            <td>
                                <span class="installment-label">Cartão {{ $p }}x</span>
                                <span style="margin-left:5px;">({{ $p }}x de R$ {{ number_format($valParc, 2, ',', '.') }})</span>
                            </td>
                            <td align="right">
                                R$ {{ number_format($totalParc, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
        
        <td class="col-pix">
            @if($shouldShowPix)
            <div class="pix-box">
                <strong style="color:#15803d; font-size:11px; text-transform:uppercase;">Pague com PIX</strong><br>
                
                @if($qrCodeImg)
                    <img src="{{ $qrCodeImg }}" class="qr-img">
                @else
                    <div style="height:100px; line-height:100px; color:#ccc; font-size:9px;">QR Code Indisponível</div>
                @endif
                
                <div style="font-size:14px; font-weight:900; color:#166534; margin-top:5px;">
                    R$ {{ number_format($totalAvista, 2, ',', '.') }}
                </div>
                
                <div class="pix-data-container">
                    <span style="font-size:7px; font-weight:bold; color:#15803d; display:block;">CHAVE PIX:</span>
                    <span class="pix-key-value">{{ $pixKey }}</span>
                    @if(!empty($beneficiario))
                        <div style="font-size:7px; color:#166534; margin-top:2px;">{{ substr($beneficiario,0,18) }}...</div>
                    @endif
                </div>
            </div>
            @endif
        </td>
    </tr>
</table>
>     <div class="footer">
    <span class="validity-text">Validade: Orçamento e QR Code válidos por 7 dias a partir da emissão.</span><br>
    Este documento não representa um contrato firmado. Após a aprovação, será gerada uma Ordem de Serviço oficial.<br>
    <span style="color:#cbd5e1; display:inline-block; margin-top:3px;">Documento gerado em {{ now()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}</span>
</div>
</body>
</html>