<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tarefa #{{ $tarefa->id }}</title>
    <style>
        @page { margin: 0; }
        @php $p = $config->pdf_color_primary ?? '#2563eb'; $t = $config->pdf_color_text ?? '#1f2937'; @endphp
        body { font-family: Arial, sans-serif; font-size: 10px; color: {{ $t }}; padding: 4.5cm 1cm 2.5cm 1cm; margin: 0; }
        .header { position: fixed; top: 0; left: 1cm; right: 1cm; height: 4cm; padding-top: 0.5cm; border-bottom: 3px solid {{ $p }}; display: flex; background: white; z-index: 1000; justify-content: space-between; }
        .footer { position: fixed; bottom: 0; left: 1cm; right: 1cm; height: 2cm; padding: 0.5cm 0; background: white; border-top: 1px solid #e5e7eb; z-index: 1000; text-align: center; font-size: 8px; color: #6b7280; }
        .section-header { background: {{ $p }}; color: white; padding: 8px 12px; font-weight: bold; font-size: 11px; text-transform: uppercase; border-radius: 4px; margin: 20px 0 10px; }
        .section-content { border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; margin-bottom: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .field-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; font-weight: 600; }
        .field-value { font-size: 10px; font-weight: 500; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 8.5px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <div>@if ($config->pdf_logo_base64)<img src="{{ $config->pdf_logo_base64 }}" style="max-width: 200px; max-height: 70px;">@endif</div>
        <div style="background: {{ $p }}; color: white; padding: 12px 16px; border-radius: 8px;"><div style="font-size: 18px; font-weight: bold;">📋 TAREFA</div></div>
    </div>
    <div class="footer"><strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>{{ now()->format('d/m/Y H:i') }}</div>
    
    <div class="section-header">INFORMAÇÕES DA TAREFA</div>
    <div class="section-content">
        <div style="font-size: 16px; font-weight: bold; margin-bottom: 12px;">{{ $tarefa->titulo }}</div>
        <div class="grid-3">
            <div><div class="field-label">Status</div><div class="field-value"><span class="badge">{{ $tarefa->status }}</span></div></div>
            <div><div class="field-label">Prioridade</div><div class="field-value"><span class="badge">{{ $tarefa->prioridade }}</span></div></div>
            <div><div class="field-label">Vencimento</div><div class="field-value">{{ $tarefa->data_vencimento ? $tarefa->data_vencimento->format('d/m/Y') : '-' }}</div></div>
        </div>
        @if($tarefa->descricao)
        <div style="margin-top: 12px;"><div class="field-label">Descrição</div><div class="field-value">{{ $tarefa->descricao }}</div></div>
        @endif
    </div>
</body>
</html>
