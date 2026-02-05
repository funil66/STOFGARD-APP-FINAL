<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato Financeiro</title>
    <style>
        @page { margin: 0px; }

        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 9px;
            color: {{ $text }};
            line-height: 1.3;
            padding-top: 4.5cm;
            padding-bottom: 2cm;
            padding-left: 1cm;
            padding-right: 1cm;
            margin: 0;
        }

        .header {
            position: fixed; top: 0; left: 1cm; right: 1cm; height: 3.5cm;
            padding-top: 0.5cm; border-bottom: 3px solid {{ $primary }};
            display: flex; justify-content: space-between; background: white; z-index: 1000;
        }

        .footer {
            position: fixed; bottom: 0; left: 1cm; right: 1cm; height: 1.5cm;
            padding-top: 5px; border-top: 1px solid #e5e7eb; background: white; z-index: 1000;
            text-align: center; color: #9ca3af; font-size: 7px;
        }

        .logo-img { max-width: 180px; max-height: 60px; margin-bottom: 5px; }
        .company-info { font-size: 8px; color: #374151; }

        .header-title {
            text-align: right; color: {{ $primary }};
        }
        .doc-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        .doc-date { font-size: 9px; opacity: 0.8; }

        .section-header {
            background: #f3f4f6; color: #374151; padding: 4px 8px;
            font-weight: bold; font-size: 9px; margin-top: 15px; margin-bottom: 8px;
            border-left: 3px solid {{ $primary }};
        }

        .filtros-box {
            font-size: 8px; color: #6b7280; margin-bottom: 10px; padding: 5px;
            border: 1px dashed #e5e7eb; border-radius: 4px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #f9fafb; padding: 5px; text-align: left; font-weight: bold; border-bottom: 2px solid #e5e7eb; font-size: 8px; }
        td { padding: 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        
        .valor-entrada { color: #166534; font-weight: bold; }
        .valor-saida { color: #991b1b; font-weight: bold; }

        .totais-box {
            background: #eff6ff; border: 1px solid {{ $primary }};
            border-radius: 6px; padding: 10px; margin-top: 10px;
            page-break-inside: avoid;
        }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 10px; }
        .total-final { border-top: 1px solid {{ $primary }}; margin-top: 6px; padding-top: 6px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            @php
                $logoPath = $config->empresa_logo ?? null;
                if ($logoPath && !file_exists($logoPath)) $logoPath = storage_path('app/public/' . $logoPath);
            @endphp
            @if($logoPath && file_exists($logoPath))
               <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
            @else
                <div style="font-size: 14px; font-weight: bold; color: {{ $primary }};">{{ $config->nome_sistema ?? 'Sistema Financeiro' }}</div>
            @endif
            <div class="company-info">
                {{ $config->empresa_cnpj ?? '' }} | {{ $config->empresa_telefone ?? '' }}<br>{{ $config->empresa_email ?? '' }}
            </div>
        </div>
        <div class="header-title">
            <div class="doc-title">Extrato Financeiro</div>
            <div class="doc-date">Gerado em: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="footer">
        Este documento é um relatório gerencial e não substitui documentos fiscais.<br>
        Sistema STOFGARD 2026
    </div>

    <!-- FILTROS -->
    @if(!empty($filtros))
    <div class="filtros-box">
        <strong>Filtros Aplicados:</strong>
        @if(!empty($filtros['periodo']['data_inicio'])) De: {{ \Carbon\Carbon::parse($filtros['periodo']['data_inicio'])->format('d/m/Y') }} @endif
        @if(!empty($filtros['periodo']['data_fim'])) Até: {{ \Carbon\Carbon::parse($filtros['periodo']['data_fim'])->format('d/m/Y') }} @endif
        @if(!empty($filtros['tipo']['value'])) | Tipo: {{ ucfirst($filtros['tipo']['value']) }} @endif
        @if(!empty($filtros['status']['value'])) | Status: {{ ucfirst($filtros['status']['value']) }} @endif
        @if(!empty($filtros['comissao_status']['value'])) | Comissões: {{ ucfirst($filtros['comissao_status']['value']) }} @endif
    </div>
    @endif

    <!-- TABELA -->
    <div class="section-header">TRANSAÇÕES DO PERÍODO</div>
    <table>
        <thead>
            <tr>
                <th width="10%">Data</th>
                <th width="35%">Descrição</th>
                <th width="15%">Categoria</th>
                <th width="20%">Cliente/Fornecedor</th>
                <th width="10%">Status</th>
                <th width="10%" style="text-align:right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transacoes as $t)
            <tr>
                <td>{{ $t->data->format('d/m/Y') }}</td>
                <td>
                    {{ $t->descricao }}
                    @if($t->is_comissao) 
                        <span style="font-size:7px; background:#fef3c7; padding:1px 3px; border-radius:3px;">COMISSÃO</span> 
                    @endif
                </td>
                <td>{{ $t->categoria?->nome }}</td>
                <td>{{ $t->cadastro?->nome }}</td>
                <td>{{ ucfirst($t->status) }}</td>
                <td style="text-align:right" class="{{ $t->tipo == 'entrada' ? 'valor-entrada' : 'valor-saida' }}">
                    {{ $t->tipo == 'entrada' ? '+' : '-' }} R$ {{ number_format($t->valor_pago > 0 ? $t->valor_pago : $t->valor, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTAIS -->
    <div style="width: 250px; margin-left: auto;">
        <div class="totais-box">
            <div class="total-row">
                <span>Total Entradas:</span>
                <span class="valor-entrada">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Total Saídas:</span>
                <span class="valor-saida">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</span>
            </div>
            <div class="total-row total-final">
                <span>SALDO DO PERÍODO:</span>
                <span style="color: {{ $saldo >= 0 ? '#166534' : '#991b1b' }}">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</body>
</html>
