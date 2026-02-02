<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garantia - {{ $garantia->numero_garantia }}</title>
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
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .badge-ativa { background: #d4edda; color: #155724; }
        .badge-utilizada { background: #fff3cd; color: #856404; }
        .badge-expirada { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>FICHA DE GARANTIA</h1>
            <div style="margin-top: 5px; font-size: 12px;">{{ $garantia->numero_garantia }}</div>
        </div>
        <div class="info">
            <div>{{ config('app.name') }}</div>
            <div>{{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-title">Informações da Garantia</div>
            <div class="grid">
                <div class="field">
                    <div class="field-label">Número da Garantia</div>
                    <div class="field-value">{{ $garantia->numero_garantia }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Status</div>
                    <div class="field-value">
                        <span class="badge badge-{{ $garantia->status }}">{{ ucfirst($garantia->status) }}</span>
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">Data de Início</div>
                    <div class="field-value">{{ $garantia->data_inicio?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Data de Fim</div>
                    <div class="field-value">{{ $garantia->data_fim?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Tipo de Serviço</div>
                    <div class="field-value">{{ $garantia->tipo_servico ?? '-' }}</div>
                </div>
                @if($garantia->usado_em)
                <div class="field">
                    <div class="field-label">Data de Uso</div>
                    <div class="field-value">{{ $garantia->usado_em->format('d/m/Y') }}</div>
                </div>
                @endif
            </div>
            @if($garantia->motivo_uso)
            <div class="field" style="margin-top: 15px;">
                <div class="field-label">Motivo de Uso</div>
                <div class="field-value">{{ $garantia->motivo_uso }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <div>Documento gerado automaticamente em {{ now()->format('d/m/Y H:i:s') }} | {{ config('app.name') }}</div>
    </div>
</body>
</html>
