<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro {{ $mes }}/{{ $ano }}</title>
    <style>
        @page { margin: 0px; }
        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $secondary = $config->pdf_color_secondary ?? '#eff6ff';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            color: {{ $text }};
            line-height: 1.4;
            padding-top: 6.2cm;
            padding-bottom: 3cm;
            padding-left: 1cm;
            padding-right: 1cm;
            margin: 0;
        }
        /* HEADER FIXO */
        .header {
            position: fixed; top: 0; left: 1cm; right: 1cm; height: 3.8cm;
            padding-top: 0.5cm; border-bottom: 3px solid {{ $primary }};
            display: flex; justify-content: space-between; align-items: flex-start;
            background: white; z-index: 1000;
        }
        /* FOOTER FIXO */
        .footer {
            position: fixed; bottom: 0; left: 1cm; right: 1cm; height: 2.5cm;
            padding-bottom: 0.5cm; background: white; padding-top: 5px;
            border-top: 1px solid #e5e7eb; z-index: 1000;
        }
        .logo-img { max-width: 200px; max-height: 70px; margin-bottom: 8px; }
        .company-info { font-size: 8.5px; color: #374151; line-height: 1.6; }
        .header-right {
            background: {{ $primary }}; color: white; padding: 12px 16px;
            border-radius: 8px; text-align: right; min-width: 170px;
        }
        .titulo-doc { font-size: 14px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
        .numero-doc { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .section-header {
            background: {{ $primary }}; color: white; padding: 7px 12px;
            font-size: 10px; font-weight: bold; margin-top: 20px;
            margin-bottom: 10px; text-transform: uppercase; page-break-after: avoid;
        }
        .footer-legal { font-size: 7px; color: #9ca3af; text-align: center; line-height: 1.5; }
        
        /* SPECIFIC STYLES */
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .summary-card { padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e5e7eb; }
        .summary-label { font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .summary-value { font-size: 16px; font-weight: bold; }
        .text-green { color: #166534; }
        .text-red { color: #991b1b; }
        .text-blue { color: #1e40af; }
        
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background: #f3f4f6; text-align: left; padding: 6px; font-weight: bold; border-bottom: 2px solid #e5e7eb; }
        td { padding: 6px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            @php
                $logoPath = $config->empresa_logo ?? null;
                if ($logoPath && !file_exists($logoPath)) $logoPath = storage_path('app/public/' . $logoPath);
            @endphp
            @if($logoPath && file_exists($logoPath))
               <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
            @else
                <div style="font-size: 16px; font-weight: bold; color: {{ $primary }}; margin-bottom: 8px;">
                    {{ $config->nome_sistema ?? 'Sistema Financeiro' }}
                </div>
            @endif
            <div class="company-info">
                {{ $config->empresa_cnpj ?? '' }}<br>
                {{ $config->empresa_telefone ?? '' }}<br>
                {{ $config->empresa_email ?? '' }}
            </div>
        </div>
        <div class="header-right">
            <div class="titulo-doc">RELATÓRIO MENSAL</div>
            <div class="numero-doc">{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}/{{ $ano }}</div>
            <div style="font-size: 9px; opacity: 0.9;">
                Gerado em: {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-legal">
            Documento gerado automaticamente pelo sistema.<br>
            <strong>Stofgard Manager</strong> - Gestão Inteligente
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <!-- SUMMARY -->
        <div class="summary-grid">
            <div class="summary-card" style="background: #f0fdf4; border-color: #bbf7d0;">
                <div class="summary-label text-green">Receitas (Entradas)</div>
                <div class="summary-value text-green">R$ {{ number_format($entradas, 2, ',', '.') }}</div>
            </div>
            <div class="summary-card" style="background: #fef2f2; border-color: #fecaca;">
                <div class="summary-label text-red">Despesas (Saídas)</div>
                <div class="summary-value text-red">R$ {{ number_format($saidas, 2, ',', '.') }}</div>
            </div>
            <div class="summary-card" style="background: #eff6ff; border-color: #bfdbfe;">
                <div class="summary-label text-blue">Resultado (Saldo)</div>
                <div class="summary-value {{ $saldo >= 0 ? 'text-blue' : 'text-red' }}">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- CATEGORIES -->
        <div class="section-header">DESPESAS POR CATEGORIA</div>
        <table style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th width="70%">CATEGORIA</th>
                    <th width="30%" class="text-right">VALOR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porCategoria as $cat)
                <tr>
                    <td>{{ $cat->categoria->nome ?? 'Sem Categoria' }}</td>
                    <td class="text-right text-red">R$ {{ number_format($cat->total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                @if($porCategoria->isEmpty())
                <tr><td colspan="2" style="text-align: center; color: #9ca3af;">Nenhuma despesa registrada neste mês.</td></tr>
                @endif
            </tbody>
        </table>

        <!-- TRANSACTIONS -->
        <div class="section-header">EXTRATO DETALHADO</div>
        <table>
            <thead>
                <tr>
                    <th width="12%">DATA</th>
                    <th width="40%">DESCRIÇÃO</th>
                    <th width="20%">CATEGORIA</th>
                    <th width="13%">STATUS</th>
                    <th width="15%" class="text-right">VALOR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transacoes as $t)
                <tr>
                    <td>{{ $t->data ? $t->data->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $t->descricao }}</strong><br>
                        <span style="font-size: 8px; color: #6b7280;">{{ $t->cadastro->nome ?? '' }}</span>
                    </td>
                    <td>{{ $t->categoria->nome ?? '-' }}</td>
                    <td>
                        <span style="
                            font-weight: bold; 
                            color: {{ $t->status === 'pago' ? '#166534' : ($t->status === 'pendente' ? '#854d0e' : '#991b1b') }};
                        ">
                            {{ strtoupper($t->status) }}
                        </span>
                    </td>
                    <td class="text-right" style="font-weight: bold; color: {{ $t->tipo === 'entrada' ? '#166534' : '#991b1b' }}">
                        {{ $t->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
