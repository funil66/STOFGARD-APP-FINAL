<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Certificado</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; color:#1f2937; margin:0; padding:30px; }
        .card { max-width:760px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        .ok { color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0; padding:10px; border-radius:8px; }
        .fail { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; padding:10px; border-radius:8px; }
        .meta { margin-top:12px; font-size:14px; line-height:1.6; }
        .hash { font-family: monospace; background:#f3f4f6; padding:4px 6px; border-radius:4px; word-break:break-all; }
    </style>
</head>
<body>
    <div class="card">
        @if($valid)
            <div class="ok"><strong>✅ Certificado válido</strong></div>
            <div class="meta">
                <div><strong>Empresa:</strong> {{ $sealData['company_name'] ?? '-' }}</div>
                <div><strong>Tipo:</strong> {{ $sealData['tipo'] ?? '-' }}</div>
                <div><strong>ID do documento:</strong> {{ $sealData['modelo_id'] ?? '-' }}</div>
                <div><strong>Data de geração:</strong> {{ $sealData['generated_at'] ?? '-' }}</div>
                <div><strong>Hash:</strong> <span class="hash">{{ $hash }}</span></div>
            </div>
        @else
            <div class="fail"><strong>❌ Certificado não encontrado</strong></div>
            <div class="meta">
                <div>O hash informado não está registrado na base de validação.</div>
                <div><strong>Hash consultado:</strong> <span class="hash">{{ $hash }}</span></div>
            </div>
        @endif
    </div>
</body>
</html>
