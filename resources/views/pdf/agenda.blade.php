<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Agendamento - #{{ $agenda->id }}</title>
    <style>
        @page {
            margin: 0px;
        }

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
            padding-top: 4.5cm;
            padding-bottom: 2.5cm;
            padding-left: 1cm;
            padding-right: 1cm;
            margin: 0;
        }

        /* HEADER FIXO */
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
            align-items: flex-start;
        }

        /* FOOTER FIXO */
        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            height: 2cm;
            padding-bottom: 0.5cm;
            background: white;
            padding-top: 5px;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
        }

        .header-left {
            max-width: 55%;
        }

        .logo-img {
            max-width: 200px;
            max-height: 70px;
            margin-bottom: 8px;
        }

        .company-info {
            font-size: 8.5px;
            color: #374151;
            line-height: 1.6;
        }

        .header-right {
            background: {{ $primary }};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: right;
            min-width: 170px;
        }

        .doc-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .doc-meta {
            font-size: 8px;
            line-height: 1.7;
        }

        /* SECTION HEADER */
        .section-header {
            background: {{ $primary }};
            color: white;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        /* INFO BOX */
        .info-box {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-grid-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding-bottom: 12px;
            padding-right: 20px;
            vertical-align: top;
        }

        .label {
            display: block;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .value {
            display: block;
            font-size: 11px;
            color: #0f172a;
            font-weight: 500;
        }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
        }

        .badge-servico { background-color: #0ea5e9; }
        .badge-visita { background-color: #f59e0b; }
        .badge-reuniao { background-color: #10b981; }
        .badge-outro { background-color: #6b7280; }

        .badge-agendado { background-color: #3b82f6; }
        .badge-em_andamento { background-color: #f59e0b; }
        .badge-concluido { background-color: #10b981; }
        .badge-cancelado { background-color: #ef4444; }

        /* RESUMO BOX */
        .resumo-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
        }

        .resumo-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 10px;
        }

        /* LOCAL BOX */
        .local-box {
            background: {{ $secondary }};
            border: 2px solid {{ $primary }};
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }

        .local-box-title {
            font-size: 10px;
            font-weight: bold;
            color: {{ $primary }};
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .local-box-content {
            font-size: 11px;
            color: #1f2937;
        }

        /* FOOTER */
        .footer-legal {
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.5;
        }

        .footer-sistema {
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
            margin-top: 5px;
        }

        /* VINCULAÇÕES */
        .vinculacao-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
        }

        .vinculacao-item {
            display: inline-block;
            padding: 6px 12px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-right: 10px;
            font-size: 10px;
        }

        .vinculacao-label {
            font-size: 8px;
            color: #6b7280;
            font-weight: 600;
        }

        .vinculacao-value {
            font-size: 11px;
            color: {{ $primary }};
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- HEADER FIXO -->
    <div class="header">
        <div class="header-left">
            @php
                $logoPath = $config->empresa_logo ?? null;
                if ($logoPath && !file_exists($logoPath)) {
                    $logoPath = storage_path('app/public/' . $logoPath);
                }
                $nomeSistema = $config->nome_sistema ?? 'Stofgard';
            @endphp
            @if($logoPath && file_exists($logoPath))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
            @else
                <div style="font-size: 16px; font-weight: bold; color: {{ $primary }}; margin-bottom: 8px;">
                    {{ $nomeSistema }}
                </div>
            @endif
            <div class="company-info">
                {{ $config->empresa_cnpj ?? '' }}<br>
                {{ $config->empresa_telefone ?? '' }}<br>
                {{ $config->empresa_email ?? '' }}
            </div>
        </div>

        <div class="header-right">
            <div style="font-size: 10px; opacity: 0.9;">FICHA DE AGENDAMENTO</div>
            <div class="doc-number">#{{ str_pad($agenda->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-meta">
                @if(!empty($agenda->id_parceiro))
                    <span style="font-weight: bold; color: yellow;">ID Parceiro: {{ $agenda->id_parceiro }}</span><br>
                @endif
                Data: {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Tipo: {{ strtoupper($agenda->tipo ?? 'SERVIÇO') }}
            </div>
        </div>
    </div>

    <!-- FOOTER FIXO -->
    <div class="footer">
        <div class="footer-legal">
            Documento gerado automaticamente pelo sistema de gestão
        </div>
        <div class="footer-sistema">
            {{ $config->nome_sistema ?? 'Stofgard' }} - Sistema Integrado de Gestão | {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div style="width: 100%;">

        <!-- INFORMAÇÕES DO AGENDAMENTO -->
        <div class="section-header">INFORMAÇÕES DO AGENDAMENTO</div>
        <div class="info-box">
            <div class="info-grid">
                <!-- TÍTULO E TIPO -->
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">Título</span>
                        <span class="value" style="font-size: 13px; font-weight: bold;">{{ strtoupper($agenda->titulo) }}</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Tipo</span>
                        <span class="badge badge-{{ $agenda->tipo ?? 'servico' }}">
                            @switch($agenda->tipo ?? 'servico')
                                @case('servico') 🧼 Serviço @break
                                @case('visita') 👁️ Visita @break
                                @case('reuniao') 🤝 Reunião @break
                                @default 📌 Outro
                            @endswitch
                        </span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Status</span>
                        <span class="badge badge-{{ $agenda->status ?? 'agendado' }}">
                            @switch($agenda->status ?? 'agendado')
                                @case('agendado') 📅 Agendado @break
                                @case('em_andamento') 🔄 Em Andamento @break
                                @case('concluido') ✅ Concluído @break
                                @case('cancelado') ❌ Cancelado @break
                                @default {{ ucfirst($agenda->status) }}
                            @endswitch
                        </span>
                    </div>
                </div>

                <!-- DATA E HORA -->
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 33%;">
                        <span class="label">Data/Hora Início</span>
                        <span class="value">{{ \Carbon\Carbon::parse($agenda->data_hora_inicio)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-cell" style="width: 33%;">
                        <span class="label">Data/Hora Fim</span>
                        <span class="value">{{ \Carbon\Carbon::parse($agenda->data_hora_fim)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-cell" style="width: 34%;">
                        <span class="label">Dia Inteiro</span>
                        <span class="value">{{ $agenda->dia_inteiro ? 'Sim' : 'Não' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLIENTE -->
        @php
            $cliente = null;
            if ($agenda->cadastro_id) {
                $cliente = \App\Models\Cadastro::find($agenda->cadastro_id);
            }
        @endphp
        @if($cliente)
        <div class="section-header">DADOS DO CLIENTE</div>
        <div class="info-box">
            <div class="info-grid">
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">Nome / Razão Social</span>
                        <span class="value" style="font-weight: bold;">{{ $cliente->nome ?? 'N/A' }}</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">WhatsApp / Celular</span>
                        <span class="value">{{ $cliente->telefone ?? $cliente->celular ?? 'N/A' }}</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">E-mail</span>
                        <span class="value">{{ $cliente->email ?? 'N/A' }}</span>
                    </div>
                </div>
                @if($cliente->logradouro || $cliente->cidade)
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">Endereço</span>
                        <span class="value">
                            {{ $cliente->logradouro ?? '' }}
                            @if($cliente->numero), {{ $cliente->numero }}@endif
                            @if($cliente->bairro) - {{ $cliente->bairro }}@endif
                        </span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Cidade / UF</span>
                        <span class="value">{{ $cliente->cidade ?? '' }}@if($cliente->estado) / {{ $cliente->estado }}@endif</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">CEP</span>
                        <span class="value">{{ $cliente->cep ?? 'N/A' }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- LOCAL DO SERVIÇO -->
        @if($agenda->endereco_completo || $agenda->local)
        <div class="section-header">📍 LOCAL DO SERVIÇO</div>
        <div class="local-box">
            <div class="local-box-content" style="font-size: 12px;">
                {{ $agenda->endereco_completo ?? $agenda->local }}
            </div>
        </div>
        @endif

        <!-- DESCRIÇÃO E OBSERVAÇÕES -->
        @if($agenda->descricao || $agenda->observacoes)
        <div class="section-header">DESCRIÇÃO E OBSERVAÇÕES</div>
        <div class="info-box">
            @if($agenda->descricao)
            <div style="margin-bottom: 10px;">
                <span class="label">Descrição</span>
                <span class="value" style="font-size: 10px; line-height: 1.5;">{{ $agenda->descricao }}</span>
            </div>
            @endif
            @if($agenda->observacoes)
            <div>
                <span class="label">Observações Internas</span>
                <span class="value" style="font-size: 10px; line-height: 1.5;">{{ $agenda->observacoes }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- VINCULAÇÕES -->
        @if($agenda->ordem_servico_id || $agenda->orcamento_id)
        <div class="section-header">VINCULAÇÕES</div>
        <div class="vinculacao-box">
            @if($agenda->ordem_servico_id)
            @php $os = \App\Models\OrdemServico::find($agenda->ordem_servico_id); @endphp
            <div class="vinculacao-item">
                <div class="vinculacao-label">Ordem de Serviço</div>
                <div class="vinculacao-value">{{ $os->numero_os ?? '#' . $agenda->ordem_servico_id }}</div>
            </div>
            @endif
            @if($agenda->orcamento_id)
            @php $orc = \App\Models\Orcamento::find($agenda->orcamento_id); @endphp
            <div class="vinculacao-item">
                <div class="vinculacao-label">Orçamento</div>
                <div class="vinculacao-value">{{ $orc->numero ?? '#' . $agenda->orcamento_id }}</div>
            </div>
            @endif
        </div>
        @endif

    </div>
</body>

</html>
