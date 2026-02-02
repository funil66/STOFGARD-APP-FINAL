<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Categoria - {{ $categoria->nome }}</title>
    <style>
        @page { margin: 0px; }
        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $secondary = $config->pdf_color_secondary ?? '#eff6ff';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            font-size: 10px;
            color: {{ $text }};
            line-height: 1.4;
            padding: 4.5cm 1cm 2.5cm 1cm;
            margin: 0;
        }
        .header {
            position: fixed;
            top: 0;
            left: 1cm;
            right: 1cm;
            height: 4cm;
            padding-top: 0.5cm;
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            background: white;
            z-index: 1000;
            justify-content: space-between;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            height: 2cm;
            padding: 0.5cm 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }
        .logo-img { max-width: 200px; max-height: 70px; margin-bottom: 8px; }
        .company-info { font-size: 8.5px; color: #374151; line-height: 1.6; }
        .header-right {
            background: {{ $primary }};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: right;
            min-width: 170px;
        }
        .doc-number { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .section-header {
            background: {{ $primary }};
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 4px;
            margin: 20px 0 10px;
        }
        .section-content {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .field { margin-bottom: 10px; }
        .field-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 600;
        }
        .field-value { font-size: 10px; color: {{ $text }}; font-weight: 500; }
        .field-value.large { font-size: 14px; font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .icon-display {
            font-size: 48px;
            text-align: center;
            padding: 20px;
            background: {{ $secondary }};
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="max-width: 55%;">
            @if ($config->pdf_logo_base64)
                <img src="{{ $config->pdf_logo_base64 }}" alt="Logo" class="logo-img">
            @endif
            <div class="company-info">
                <strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>
                @if ($config->cnpj) CNPJ: {{ $config->cnpj }}<br> @endif
                @if ($config->telefone) Telefone: {{ $config->telefone }}<br> @endif
                @if ($config->endereco) {{ $config->endereco }} @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-number">📁 CATEGORIA</div>
            <div style="font-size: 8px; line-height: 1.7;">
                <strong>ID:</strong> #{{ $categoria->id }}<br>
                <strong>Slug:</strong> {{ $categoria->slug }}
            </div>
        </div>
    </div>

    <div class="footer">
        <strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>
        Documento gerado em {{ now()->format('d/m/Y às H:i') }}
    </div>

    <div class="section-header">📋 INFORMAÇÕES DA CATEGORIA</div>
    <div class="section-content">
        <div class="grid-2">
            <div>
                <div class="field">
                    <div class="field-label">Nome</div>
                    <div class="field-value large">{{ $categoria->nome }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Tipo</div>
                    <div class="field-value">
                        @php
                            $tipoLabel = match($categoria->tipo) {
                                'financeiro_receita' => '💰 Receita Financeira',
                                'financeiro_despesa' => '💸 Despesa Financeira',
                                'produto' => '📦 Produto',
                                default => $categoria->tipo
                            };
                        @endphp
                        <span class="badge badge-info">{{ $tipoLabel }}</span>
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">Status</div>
                    <div class="field-value">
                        <span class="badge {{ $categoria->ativo ? 'badge-success' : 'badge-danger' }}">
                            {{ $categoria->ativo ? '✅ ATIVO' : '❌ INATIVO' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="icon-display">
                {{ $categoria->icone ?? '📌' }}
            </div>
        </div>
    </div>

    <div class="section-header">🎨 DETALHES VISUAIS</div>
    <div class="section-content">
        <div class="grid-3">
            <div class="field">
                <div class="field-label">Ícone</div>
                <div class="field-value" style="font-size: 24px;">{{ $categoria->icone ?? '📌' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Cor</div>
                <div class="field-value">
                    <span style="display: inline-block; width: 50px; height: 20px; background: {{ $categoria->cor }}; border-radius: 4px; border: 1px solid #e5e7eb;"></span>
                    <span style="margin-left: 8px;">{{ $categoria->cor }}</span>
                </div>
            </div>
            <div class="field">
                <div class="field-label">Ordem de Exibição</div>
                <div class="field-value">{{ $categoria->ordem ?? 0 }}</div>
            </div>
        </div>
        @if ($categoria->descricao)
            <div class="field">
                <div class="field-label">Descrição</div>
                <div class="field-value">{{ $categoria->descricao }}</div>
            </div>
        @endif
    </div>

    <div class="section-header">📊 METADADOS</div>
    <div class="section-content">
        <div class="grid-2">
            <div class="field">
                <div class="field-label">Identificador (Slug)</div>
                <div class="field-value" style="font-family: monospace; background: #f9fafb; padding: 4px 8px; border-radius: 4px;">
                    {{ $categoria->slug }}
                </div>
            </div>
            <div class="field">
                <div class="field-label">ID no Sistema</div>
                <div class="field-value">#{{ $categoria->id }}</div>
            </div>
            <div class="field">
                <div class="field-label">Criado em</div>
                <div class="field-value">{{ $categoria->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="field">
                <div class="field-label">Última Atualização</div>
                <div class="field-value">{{ $categoria->updated_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</body>
</html>
