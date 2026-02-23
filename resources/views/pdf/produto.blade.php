<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produto - {{ $produto->nome }}</title>
    <style>
        @page { margin: 0; }
        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $secondary = $config->pdf_color_secondary ?? '#eff6ff';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp
        body { font-family: Arial, sans-serif; font-size: 10px; color: {{ $text }}; padding: 4.5cm 1cm 2.5cm 1cm; margin: 0; }
        .header { position: fixed; top: 0; left: 1cm; right: 1cm; height: 4cm; padding-top: 0.5cm; border-bottom: 3px solid {{ $primary }}; display: flex; background: white; z-index: 1000; justify-content: space-between; }
        .footer { position: fixed; bottom: 0; left: 1cm; right: 1cm; height: 2cm; padding: 0.5cm 0; background: white; border-top: 1px solid #e5e7eb; z-index: 1000; text-align: center; font-size: 8px; color: #6b7280; }
        .logo-img { max-width: 200px; max-height: 70px; margin-bottom: 8px; }
        .company-info { font-size: 8.5px; color: #374151; line-height: 1.6; }
        .header-right { background: {{ $primary }}; color: white; padding: 12px 16px; border-radius: 8px; text-align: right; min-width: 170px; }
        .doc-number { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .section-header { background: {{ $primary }}; color: white; padding: 8px 12px; font-weight: bold; font-size: 11px; text-transform: uppercase; border-radius: 4px; margin: 20px 0 10px; }
        .section-content { background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; margin-bottom: 16px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .field { margin-bottom: 10px; }
        .field-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; font-weight: 600; }
        .field-value { font-size: 10px; color: {{ $text }}; font-weight: 500; }
        .field-value.large { font-size: 16px; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 8.5px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <div style="max-width: 55%;">
            @if ($config->pdf_logo_base64)<img src="{{ $config->pdf_logo_base64 }}" class="logo-img">@endif
            <div class="company-info"><strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>
            @if($config->cnpj)CNPJ: {{ $config->cnpj }}<br>@endif</div>
        </div>
        <div class="header-right">
            <div class="doc-number">📦 PRODUTO</div>
            <div style="font-size: 8px;"><strong>ID:</strong> #{{ $produto->id }}</div>
        </div>
    </div>
    <div class="footer"><strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>Gerado em {{ now()->format('d/m/Y H:i') }}</div>

    <div class="section-header">📋 INFORMAÇÕES DO PRODUTO</div>
    <div class="section-content">
        <div class="field"><div class="field-label">Nome</div><div class="field-value large">{{ $produto->nome }}</div></div>
        <div class="grid-3">
            <div class="field"><div class="field-label">Preço de Venda</div><div class="field-value large" style="color: #10b981;">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</div></div>
            <div class="field"><div class="field-label">Preço de Custo</div><div class="field-value">R$ {{ number_format($produto->preco_custo ?? 0, 2, ',', '.') }}</div></div>
            <div class="field"><div class="field-label">Margem</div><div class="field-value">
                @php
                    if($produto->preco_custo && $produto->preco_venda && $produto->preco_custo > 0) {
                        $margem = (($produto->preco_venda - $produto->preco_custo) / $produto->preco_custo) * 100;
                        echo number_format($margem, 2) . '%';
                    } else {
                        echo 'N/A';
                    }
                @endphp
            </div></div>
        </div>
    </div>

    <div class="section-header">📦 ESTOQUE</div>
    <div class="section-content">
        <div class="grid-3">
            <div class="field"><div class="field-label">Quantidade em Estoque</div>
                <div class="field-value"><span class="badge {{ $produto->estoque_atual > 10 ? 'badge-success' : ($produto->estoque_atual > 0 ? 'badge-warning' : 'badge-danger') }}">{{ $produto->estoque_atual ?? 0 }}</span></div>
            </div>
            <div class="field"><div class="field-label">Estoque Mínimo</div><div class="field-value">{{ $produto->estoque_minimo ?? 'Não definido' }}</div></div>
            <div class="field"><div class="field-label">Unidade</div><div class="field-value">{{ $produto->unidade_medida ?? 'UN' }}</div></div>
        </div>
    </div>

    @if($produto->descricao)
    <div class="section-header">📝 DESCRIÇÃO</div>
    <div class="section-content"><div class="field-value">{{ $produto->descricao }}</div></div>
    @endif
</body>
</html>
