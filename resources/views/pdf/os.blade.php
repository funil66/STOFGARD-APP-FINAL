<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS #{{ $record->numero_os ?? $record->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 15mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.4;
        }

        /* HEADER */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-img {
            max-width: 180px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .header-left h1 {
            font-size: 16px;
            color: #2563eb;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .header-left h2 {
            font-size: 14px;
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header-left .subline {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .company-info {
            font-size: 8.5px;
            color: #374151;
            line-height: 1.6;
        }

        .header-right {
            background: #2563eb;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            text-align: right;
            min-width: 180px;
        }

        .numero-orcamento {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .datas {
            font-size: 8px;
            line-height: 1.7;
        }

        /* SECTION HEADERS */
        .section-header {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        /* CLIENT INFO */
        .client-info {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }

        .client-name {
            font-size: 11px;
            font-weight: bold;
        }

        .client-phone {
            font-size: 10px;
            color: #6b7280;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        table thead {
            background: #f3f4f6;
        }

        table thead th {
            padding: 9px 7px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }

        table thead th:nth-child(4),
        table thead th:nth-child(5) {
            text-align: right;
        }

        table tbody td {
            padding: 10px 7px;
            font-size: 9px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table tbody td:nth-child(4),
        table tbody td:nth-child(5) {
            text-align: right;
        }

        .item-category {
            background: #dbeafe;
            color: #1e40af;
            padding: 3px 7px;
            border-radius: 3px;
            display: inline-block;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .item-description {
            color: #6b7280;
            font-size: 8.5px;
            line-height: 1.4;
            margin-top: 2px;
        }

        /* VALUES BOX */
        .valores-section {
            margin-top: 20px;
        }

        .valores-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
        }

        .valor-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 10px;
        }

        .valor-row.total .label {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
        }

        .valor-row.total .value {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @php
                    $logoPath = null;
                    if (isset($config->empresa_logo) && $config->empresa_logo) {
                        $logoPath = storage_path('app/public/' . $config->empresa_logo);
                    }
                @endphp

                @if($logoPath && file_exists($logoPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo"
                        class="logo-img">
                @else
                    <h1>STOFGARD</h1>
                    <h2>ORDEM DE SERVIÇO</h2>
                @endif

                <div class="company-info">
                    <div><strong>CNPJ:</strong> {{ $config->empresa_cnpj ?? '00.000.000/0001-00' }}</div>
                    <div><strong>Telefone:</strong> {{ $config->empresa_telefone ?? '(16) 99999-9999' }}</div>
                </div>
            </div>
            <div class="header-right">
                <div class="numero-orcamento">OS #{{ $record->numero_os ?? $record->id }}</div>
                <div class="datas">
                    <div><strong>Data:</strong> {{ $record->created_at->format('d/m/Y H:i') }}</div>
                    <div><strong>Status:</strong> {{ strtoupper($record->status) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DADOS DO CLIENTE -->
    <div class="section-header">DADOS DO CLIENTE</div>
    <div class="client-info">
        <div class="client-name">CLIENTE: {{ strtoupper($record->cliente->nome ?? 'Cliente Não Informado') }}</div>
        <div class="client-phone">TELEFONE: {{ $record->cliente->telefone ?? '(--) -----' }}</div>
    </div>
    <div style="font-size: 9px; margin-top: 5px;">ENDEREÇO:
        {{ $record->endereco ?? $record->cliente->endereco ?? 'Endereço não informado' }}</div>

    <!-- ITENS DA OS -->
    <div class="section-header">ITENS DO SERVIÇO</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Serviço / Produto</th>
                <th style="width: 10%;">Qtd</th>
                <th style="width: 20%;">Valor Unit.</th>
                <th style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if($record->itens && count($record->itens) > 0)
                @foreach($record->itens as $item)
                    <tr>
                        <td>
                            <div class="item-description">{{ $item['descricao'] ?? $item['nome'] ?? 'Item sem nome' }}</div>
                        </td>
                        <td>{{ $item['quantidade'] ?? 1 }}</td>
                        <td>R$ {{ number_format($item['valor_unitario'] ?? 0, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($item['subtotal'] ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4">Nenhum item registrado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- VALORES -->
    <div class="section-header">VALORES</div>
    <div class="valores-box">
        <div class="valor-row total">
            <span class="label">VALOR TOTAL:</span>
            <span class="value">R$ {{ number_format($record->valor_total, 2, ',', '.') }}</span>
        </div>
    </div>

    <!-- OBSERVAÇÕES -->
    @if($record->observacoes)
        <div class="section-header">OBSERVAÇÕES</div>
        <div style="padding: 10px; font-size: 10px; color: #555;">
            {{ $record->observacoes }}
        </div>
    @endif

    <!-- ASSINATURAS -->
    <div style="margin-top: 60px; display: flex; justify-content: space-between; padding-top: 20px;">
        <div style="width: 40%; border-top: 1px solid #000; text-align: center; font-size: 10px;">
            ASSINATURA DO TÉCNICO
        </div>
        <div style="width: 40%; border-top: 1px solid #000; text-align: center; font-size: 10px;">
            ASSINATURA DO CLIENTE
        </div>
    </div>
</body>

</html>