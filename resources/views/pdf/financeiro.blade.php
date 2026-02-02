<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante Financeiro - #{{ $financeiro->id }}</title>
    <style>
        @page {
            margin: 0px;
        }

        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $secondary = $config->pdf_color_secondary ?? '#eff6ff';
            $text = $config->pdf_color_text ?? '#1f2937';
            $tipoCor = $financeiro->tipo === 'entrada' ? '#10b981' : '#ef4444';
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
            background: {{ $tipoCor }};
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
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .section-content {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }

        .field {
            margin-bottom: 10px;
        }

        .field-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .field-value {
            font-size: 10px;
            color: {{ $text }};
            font-weight: 500;
        }

        .field-value.large {
            font-size: 14px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .valor-total {
            background: {{ $secondary }};
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid {{ $tipoCor }};
            margin-top: 10px;
        }

        .footer-text {
            font-size: 8px;
            color: #6b7280;
            text-align: center;
            line-height: 1.6;
        }

        .footer-divider {
            border-top: 1px solid #e5e7eb;
            margin: 8px 0;
        }

        .observacoes {
            background: #f9fafb;
            padding: 10px;
            border-left: 3px solid {{ $primary }};
            font-size: 9px;
            line-height: 1.6;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            @if ($config->pdf_logo_base64)
                <img src="{{ $config->pdf_logo_base64 }}" alt="Logo" class="logo-img">
            @endif
            <div class="company-info">
                <strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>
                @if ($config->cnpj)
                    CNPJ: {{ $config->cnpj }}<br>
                @endif
                @if ($config->telefone)
                    Telefone: {{ $config->telefone }}<br>
                @endif
                @if ($config->endereco)
                    {{ $config->endereco }}
                @endif
            </div>
        </div>

        <div class="header-right">
            <div class="doc-number">{{ $financeiro->tipo === 'entrada' ? '💰 RECEITA' : '💸 DESPESA' }}</div>
            <div class="doc-meta">
                <strong>ID:</strong> #{{ $financeiro->id }}<br>
                <strong>Emissão:</strong> {{ $financeiro->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-divider"></div>
        <div class="footer-text">
            <strong>{{ $config->nome_empresa ?? 'Empresa' }}</strong><br>
            Este documento foi gerado automaticamente pelo sistema em {{ now()->format('d/m/Y às H:i') }}
        </div>
    </div>

    <!-- CONTEÚDO -->
    
    <!-- INFORMAÇÕES PRINCIPAIS -->
    <div class="section-header">📋 INFORMAÇÕES DA TRANSAÇÃO</div>
    <div class="section-content">
        <div class="grid-3" style="margin-bottom: 12px;">
            <div class="field">
                <div class="field-label">Tipo de Transação</div>
                <div class="field-value">
                    <span class="badge {{ $financeiro->tipo === 'entrada' ? 'badge-success' : 'badge-danger' }}">
                        {{ $financeiro->tipo === 'entrada' ? '💰 ENTRADA' : '💸 SAÍDA' }}
                    </span>
                </div>
            </div>
            <div class="field">
                <div class="field-label">Status</div>
                <div class="field-value">
                    @php
                        $statusClass = match($financeiro->status) {
                            'pago' => 'badge-success',
                            'vencido' => 'badge-danger',
                            'pendente' => 'badge-warning',
                            default => 'badge-info'
                        };
                        $statusLabel = match($financeiro->status) {
                            'pago' => '✅ PAGO',
                            'pendente' => '⏳ PENDENTE',
                            'vencido' => '🔴 VENCIDO',
                            'cancelado' => '❌ CANCELADO',
                            default => strtoupper($financeiro->status)
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
            <div class="field">
                <div class="field-label">Categoria</div>
                <div class="field-value">
                    @if ($financeiro->categoria)
                        <span class="badge badge-info">
                            {{ $financeiro->categoria->icone ?? '📌' }} {{ $financeiro->categoria->nome }}
                        </span>
                    @else
                        <span style="color: #9ca3af;">Sem categoria</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="field">
            <div class="field-label">Descrição</div>
            <div class="field-value">{{ $financeiro->descricao }}</div>
        </div>

        @if ($financeiro->forma_pagamento)
            <div class="field">
                <div class="field-label">Forma de Pagamento</div>
                <div class="field-value">
                    @php
                        $formaPagamentoLabel = match($financeiro->forma_pagamento) {
                            'pix' => '💳 PIX',
                            'dinheiro' => '💵 Dinheiro',
                            'cartao_credito' => '💳 Cartão de Crédito',
                            'cartao_debito' => '💳 Cartão de Débito',
                            'boleto' => '📄 Boleto',
                            'transferencia' => '🏦 Transferência',
                            default => $financeiro->forma_pagamento
                        };
                    @endphp
                    {{ $formaPagamentoLabel }}
                </div>
            </div>
        @endif
    </div>

    <!-- DATAS -->
    <div class="section-header">📅 DATAS</div>
    <div class="section-content">
        <div class="grid-3">
            <div class="field">
                <div class="field-label">Data do Lançamento</div>
                <div class="field-value">{{ $financeiro->data->format('d/m/Y') }}</div>
            </div>
            <div class="field">
                <div class="field-label">Data de Vencimento</div>
                <div class="field-value" style="color: {{ $financeiro->status === 'vencido' ? '#dc2626' : 'inherit' }};">
                    {{ $financeiro->data_vencimento ? $financeiro->data_vencimento->format('d/m/Y') : 'Não definido' }}
                </div>
            </div>
            <div class="field">
                <div class="field-label">Data do Pagamento</div>
                <div class="field-value" style="color: #10b981;">
                    {{ $financeiro->data_pagamento ? $financeiro->data_pagamento->format('d/m/Y H:i') : 'Não pago' }}
                </div>
            </div>
        </div>
    </div>

    <!-- VALORES -->
    <div class="section-header">💵 DETALHAMENTO DE VALORES</div>
    <div class="section-content">
        <div class="grid-2">
            <div class="field">
                <div class="field-label">Valor Original</div>
                <div class="field-value large">R$ {{ number_format($financeiro->valor, 2, ',', '.') }}</div>
            </div>
            @if ($financeiro->desconto > 0)
                <div class="field">
                    <div class="field-label">Desconto</div>
                    <div class="field-value" style="color: #10b981;">- R$ {{ number_format($financeiro->desconto, 2, ',', '.') }}</div>
                </div>
            @endif
            @if ($financeiro->juros > 0)
                <div class="field">
                    <div class="field-label">Juros</div>
                    <div class="field-value" style="color: #f59e0b;">+ R$ {{ number_format($financeiro->juros, 2, ',', '.') }}</div>
                </div>
            @endif
            @if ($financeiro->multa > 0)
                <div class="field">
                    <div class="field-label">Multa</div>
                    <div class="field-value" style="color: #ef4444;">+ R$ {{ number_format($financeiro->multa, 2, ',', '.') }}</div>
                </div>
            @endif
        </div>

        <div class="valor-total">
            <div class="grid-2">
                <div class="field" style="margin-bottom: 0;">
                    <div class="field-label">Valor Total</div>
                    <div class="field-value large" style="color: {{ $tipoCor }};">
                        R$ {{ number_format($financeiro->valor_total, 2, ',', '.') }}
                    </div>
                </div>
                @if ($financeiro->valor_pago > 0)
                    <div class="field" style="margin-bottom: 0;">
                        <div class="field-label">Valor Pago</div>
                        <div class="field-value large" style="color: #10b981;">
                            R$ {{ number_format($financeiro->valor_pago, 2, ',', '.') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- VINCULAÇÕES -->
    @if ($financeiro->cadastro || $financeiro->ordem_servico_id || $financeiro->orcamento_id)
        <div class="section-header">🔗 VINCULAÇÕES</div>
        <div class="section-content">
            <div class="grid-3">
                @if ($financeiro->cadastro)
                    <div class="field">
                        <div class="field-label">Cliente/Fornecedor</div>
                        <div class="field-value">
                            {{ $financeiro->cadastro->nome }}<br>
                            @if ($financeiro->cadastro->celular)
                                <span style="font-size: 9px; color: #6b7280;">{{ $financeiro->cadastro->celular }}</span>
                            @endif
                        </div>
                    </div>
                @endif
                @if ($financeiro->ordemServico)
                    <div class="field">
                        <div class="field-label">Ordem de Serviço</div>
                        <div class="field-value">OS #{{ $financeiro->ordemServico->numero_os }}</div>
                    </div>
                @endif
                @if ($financeiro->orcamento)
                    <div class="field">
                        <div class="field-label">Orçamento</div>
                        <div class="field-value">{{ $financeiro->orcamento->numero }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- OBSERVAÇÕES -->
    @if ($financeiro->observacoes)
        <div class="section-header">📝 OBSERVAÇÕES</div>
        <div class="section-content">
            <div class="observacoes">
                {{ $financeiro->observacoes }}
            </div>
        </div>
    @endif

</body>

</html>
