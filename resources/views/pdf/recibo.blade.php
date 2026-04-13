<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        @page { margin: 0; }
        @php
            $primary = data_get($config, 'pdf_color_primary', '#2563eb');
            $text = data_get($config, 'pdf_color_text', '#1f2937');
        @endphp
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 14px;
            color: {{ $text }};
            line-height: 1.6;
            margin: 0;
            padding: 2cm;
        }
        .header { text-align: center; margin-bottom: 2cm; }
        .logo-img { max-width: 250px; max-height: 100px; margin-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; color: {{ $primary }}; margin-bottom: 5px; }
        .title { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 1.5cm; text-transform: uppercase; letter-spacing: 2px; }
        .content { font-size: 16px; text-align: justify; margin-bottom: 2cm; }
        .value-box { font-size: 20px; font-weight: bold; text-align: right; margin-bottom: 1cm; color: {{ $primary }}; }
        .signature-area { margin-top: 3cm; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 60%; margin: 0 auto 10px auto; padding-top: 5px; }
        .selo-box { margin-top: 2cm; font-size: 10px; color: #666; text-align: center; border-top: 1px dashed #ccc; padding-top: 10px; }
        .badge-image { max-width: 200px; max-height: 100px; margin-bottom: 10px; }
    </style>
</head>
<body>
    @php
        $logoPath = $config->empresa_logo ?? null;
        if ($logoPath && !file_exists($logoPath)) {
            $logoPath = storage_path('app/public/' . $logoPath);
        }
        $valorFormatado = number_format($record->valor_pago > 0 ? $record->valor_pago : $record->valor, 2, ',', '.');
        
        // Converter valor para extenso
        $f = new \NumberFormatter('pt_BR', \NumberFormatter::SPELLOUT);
        $valorInt = floor($record->valor_pago > 0 ? $record->valor_pago : $record->valor);
        $centavos = round((($record->valor_pago > 0 ? $record->valor_pago : $record->valor) - $valorInt) * 100);
        $extenso = $f->format($valorInt) . ' reais';
        if ($centavos > 0) {
            $extenso .= ' e ' . $f->format($centavos) . ' centavos';
        }
        
        $servicos = '';
        if ($record->ordemServico) {
            $itensCount = $record->ordemServico->itens ? $record->ordemServico->itens->count() : 0;
            $items = $itensCount > 0 ? implode(', ', $record->ordemServico->itens->pluck('nome')->toArray()) : '';
            $servicos = "Referente a Ordem de Serviço #{$record->ordemServico->numero_os} - " . ($record->ordemServico->tipo_servico ?? '');
            if ($items) {
                $servicos .= " ($items)";
            }
        } else {
            $servicos = "Referente a " . ($record->descricao ?? 'Serviços prestados');
        }
    @endphp

    <div class="header">
        @if($logoPath && file_exists($logoPath))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
        @endif
        <div class="company-name">{{ $config->empresa_nome ?? 'Nossa Empresa' }}</div>
        <div>{{ $config->empresa_cnpj ?? '' }} | {{ $config->empresa_telefone ?? '' }}</div>
    </div>

    <div class="title">RECIBO</div>

    <div class="value-box">
        VALOR: R$ {{ $valorFormatado }}
    </div>

    <div class="content">
        <strong>{{ $config->empresa_nome ?? 'Nossa Empresa' }}</strong> declara que recebeu de <strong>{{ $record->cadastro->nome ?? 'Cliente' }}</strong>, a importância de <strong>R$ {{ $valorFormatado }}</strong> ({{ $extenso }}), referente a {{ $servicos }}.<br><br>
        Por ser verdade, firmamos o presente recibo.
    </div>

    <div style="text-align: right; margin-bottom: 2cm;">
        Data: {{ now()->format('d/m/Y') }}
    </div>

    <div class="signature-area">
        @if($record->assinatura_recibo)
            <img src="{{ $record->assinatura_recibo }}" class="badge-image" alt="Assinatura">
            <div style="font-size: 12px; color: #666; margin-bottom: 10px;">Assinado Eletronicamente por IP: {{ $record->assinatura_recibo_ip }} em {{ $record->assinatura_recibo_timestamp?->format('d/m/Y H:i:s') }}</div>
        @else
            <br><br>
        @endif
        <div class="signature-line"></div>
        <div>{{ $config->empresa_nome ?? 'Emissor' }}</div>
        @if($config->empresa_cnpj)
            <div style="font-size: 12px; color: #666;">CNPJ: {{ $config->empresa_cnpj }}</div>
        @endif
    </div>

    @if($record->recibo_selo)
    <div class="selo-box">
        <strong>SELO DE AUTENTICIDADE:</strong> {{ $record->recibo_selo }}<br>
        Documento gerado e registrado eletronicamente.
    </div>
    @endif
</body>
</html>
