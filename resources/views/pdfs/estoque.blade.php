<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - {{ $estoque->item }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { position: fixed; top: 0; left: 0; right: 0; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; font-weight: bold; }
        .header .info { text-align: right; font-size: 10px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 40px; background: #f8f9fa; border-top: 2px solid #667eea; padding: 12px 40px; text-align: center; font-size: 9px; color: #666; }
        .content { margin-top: 100px; margin-bottom: 60px; padding: 0 40px; }
        .section { margin-bottom: 20px; }
        .section-title { background: #667eea; color: white; padding: 8px 15px; font-size: 13px; font-weight: bold; margin-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .field { margin-bottom: 10px; }
        .field-label { font-weight: bold; color: #666; font-size: 9px; text-transform: uppercase; margin-bottom: 3px; }
        .field-value { font-size: 11px; color: #333; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>FICHA DE ESTOQUE</h1>
            <div style="margin-top: 5px; font-size: 12px;">{{ $estoque->item }}</div>
        </div>
        <div class="info">
            <div>{{ config('app.name') }}</div>
            <div>{{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-title">Informações do Estoque</div>
            <div class="grid">
                <div class="field">
                    <div class="field-label">Item</div>
                    <div class="field-value">{{ $estoque->item }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Quantidade</div>
                    <div class="field-value">{{ $estoque->quantidade }} L</div>
                </div>
                <div class="field">
                    <div class="field-label">Unidade</div>
                    <div class="field-value">{{ $estoque->unidade }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Mínimo</div>
                    <div class="field-value">{{ $estoque->minimo }} L</div>
                </div>
            </div>
            @if($estoque->observacoes)
            <div class="field" style="margin-top: 15px;">
                <div class="field-label">Observações</div>
                <div class="field-value">{{ $estoque->observacoes }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <div>Documento gerado automaticamente em {{ now()->format('d/m/Y H:i:s') }} | {{ config('app.name') }}</div>
    </div>
</body>
</html>
