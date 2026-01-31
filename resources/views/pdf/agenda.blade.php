<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento #{{ $agenda->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            line-height: 1.6;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #2563eb;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            color: #374151;
        }

        .value {
            color: #111;
        }

        .section {
            margin-top: 20px;
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
        }

        h1 {
            margin: 0;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        .status-agendado {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-concluido {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelado {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="row">
            <h1>DETALHES DO AGENDAMENTO</h1>
            <div>#{{ $agenda->id }}</div>
        </div>
    </div>

    <div class="row">
        <div>
            <div class="label">CLIENTE</div>
            <div class="value">{{ $agenda->cliente->nome ?? 'N/A' }}</div>
            <div class="value" style="font-size: 12px; color: #666;">{{ $agenda->cliente->telefone ?? 'Sem telefone' }}
            </div>
        </div>
        <div style="text-align: right;">
            <div class="label">DATA E HORA</div>
            <div class="value">{{ \Carbon\Carbon::parse($agenda->data_hora_inicio)->format('d/m/Y H:i') }}</div>
            <div class="value" style="font-size: 12px; color: #666;">até
                {{ \Carbon\Carbon::parse($agenda->data_hora_fim)->format('H:i') }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="label">DESCRIÇÃO / TÍTULO</div>
        <div class="value">{{ $agenda->titulo }}</div>

        @if($agenda->descricao)
            <div class="label" style="margin-top: 10px;">OBSERVAÇÕES</div>
            <div class="value">{{ $agenda->descricao }}</div>
        @endif
    </div>

    <div class="row" style="margin-top: 20px; align-items: center;">
        <div class="label">STATUS ATUAL:</div>
        <span class="status status-{{ $agenda->status }}">{{ $agenda->status }}</span>
    </div>

    @if($agenda->endereco_completo || $agenda->local)
        <div class="section" style="background: #fff; border: 1px solid #e5e7eb;">
            <div class="label">LOCAL DO SERVIÇO</div>
            <div class="value">{{ $agenda->endereco_completo ?? $agenda->local }}</div>
        </div>
    @endif
</body>

</html>