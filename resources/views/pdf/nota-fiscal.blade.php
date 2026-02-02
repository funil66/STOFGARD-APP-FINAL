<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Fiscal - {{ $notaFiscal->numero_nf }}</title>
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

        .grid-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
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

        .chave-acesso {
            background: #f9fafb;
            padding: 8px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 9px;
            word-break: break-all;
            border: 1px solid #e5e7eb;
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

        .tributos-box {
            background: {{ $secondary }};
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid {{ $primary }};
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
            <div class="doc-number">📄 NF-e {{ $notaFiscal->numero_nf }}</div>
            <div class="doc-meta">
                <strong>Série:</strong> {{ $notaFiscal->serie }}<br>
                <strong>Emissão:</strong> {{ $notaFiscal->data_emissao ? \Carbon\Carbon::parse($notaFiscal->data_emissao)->format('d/m/Y') : '-' }}
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
    
    <!-- INFORMAÇÕES DA NOTA -->
    <div class="section-header">📋 INFORMAÇÕES DA NOTA FISCAL</div>
    <div class="section-content">
        <div class="grid-4" style="margin-bottom: 12px;">
            <div class="field">
                <div class="field-label">Número da NF</div>
                <div class="field-value large">{{ $notaFiscal->numero_nf }}</div>
            </div>
            <div class="field">
                <div class="field-label">Série</div>
                <div class="field-value">{{ $notaFiscal->serie }}</div>
            </div>
            <div class="field">
                <div class="field-label">Tipo</div>
                <div class="field-value">
                    <span class="badge badge-info">
                        {{ $notaFiscal->tipo === 'entrada' ? '📥 ENTRADA' : '📤 SAÍDA' }}
                    </span>
                </div>
            </div>
            <div class="field">
                <div class="field-label">Status</div>
                <div class="field-value">
                    @php
                        $statusClass = match($notaFiscal->status) {
                            'autorizada' => 'badge-success',
                            'cancelada' => 'badge-danger',
                            'pendente' => 'badge-warning',
                            default => 'badge-info'
                        };
                        $statusLabel = match($notaFiscal->status) {
                            'autorizada' => '✅ AUTORIZADA',
                            'cancelada' => '❌ CANCELADA',
                            'pendente' => '⏳ PENDENTE',
                            'rejeitada' => '🚫 REJEITADA',
                            default => strtoupper($notaFiscal->status ?? 'n/a')
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <div class="field">
                <div class="field-label">Modelo</div>
                <div class="field-value">{{ $notaFiscal->modelo ?? 'NF-e' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Data de Emissão</div>
                <div class="field-value">
                    {{ $notaFiscal->data_emissao ? \Carbon\Carbon::parse($notaFiscal->data_emissao)->format('d/m/Y H:i') : 'Não informado' }}
                </div>
            </div>
            <div class="field">
                <div class="field-label">Protocolo de Autorização</div>
                <div class="field-value">{{ $notaFiscal->protocolo_autorizacao ?? 'Não autorizado' }}</div>
            </div>
        </div>

        @if ($notaFiscal->chave_acesso)
            <div class="field">
                <div class="field-label">Chave de Acesso</div>
                <div class="chave-acesso">{{ $notaFiscal->chave_acesso }}</div>
            </div>
        @endif
    </div>

    <!-- DADOS DO CLIENTE -->
    @if ($notaFiscal->cadastro_id)
        <div class="section-header">👤 DADOS DO DESTINATÁRIO</div>
        <div class="section-content">
            @php
                $cliente = null;
                if (str_starts_with($notaFiscal->cadastro_id, 'cliente_')) {
                    $id = (int) str_replace('cliente_', '', $notaFiscal->cadastro_id);
                    $cliente = \App\Models\Cliente::find($id);
                } elseif (str_starts_with($notaFiscal->cadastro_id, 'parceiro_')) {
                    $id = (int) str_replace('parceiro_', '', $notaFiscal->cadastro_id);
                    $cliente = \App\Models\Parceiro::find($id);
                } else {
                    $cliente = \App\Models\Cadastro::find($notaFiscal->cadastro_id);
                }
            @endphp

            @if ($cliente)
                <div class="grid-2">
                    <div class="field">
                        <div class="field-label">Nome/Razão Social</div>
                        <div class="field-value">{{ $cliente->nome }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">CPF/CNPJ</div>
                        <div class="field-value">{{ $cliente->cpf ?? $cliente->cnpj ?? 'Não informado' }}</div>
                    </div>
                </div>
                @if ($cliente->endereco ?? $cliente->celular)
                    <div class="grid-2">
                        @if ($cliente->endereco ?? false)
                            <div class="field">
                                <div class="field-label">Endereço</div>
                                <div class="field-value">{{ $cliente->endereco }}</div>
                            </div>
                        @endif
                        @if ($cliente->celular ?? false)
                            <div class="field">
                                <div class="field-label">Telefone</div>
                                <div class="field-value">{{ $cliente->celular }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="field-value" style="color: #9ca3af;">Cliente não identificado</div>
            @endif
        </div>
    @endif

    <!-- VALORES -->
    <div class="section-header">💵 VALORES</div>
    <div class="section-content">
        <div class="grid-2">
            <div class="field">
                <div class="field-label">Valor de Produtos</div>
                <div class="field-value">R$ {{ number_format($notaFiscal->valor_produtos ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="field">
                <div class="field-label">Valor de Serviços</div>
                <div class="field-value">R$ {{ number_format($notaFiscal->valor_servicos ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>

        @if ($notaFiscal->valor_desconto > 0)
            <div class="field">
                <div class="field-label">Desconto</div>
                <div class="field-value" style="color: #10b981;">- R$ {{ number_format($notaFiscal->valor_desconto, 2, ',', '.') }}</div>
            </div>
        @endif

        <div class="tributos-box">
            <div class="grid-4">
                @if ($notaFiscal->valor_icms > 0)
                    <div class="field" style="margin-bottom: 0;">
                        <div class="field-label">ICMS</div>
                        <div class="field-value">R$ {{ number_format($notaFiscal->valor_icms, 2, ',', '.') }}</div>
                    </div>
                @endif
                @if ($notaFiscal->valor_iss > 0)
                    <div class="field" style="margin-bottom: 0;">
                        <div class="field-label">ISS</div>
                        <div class="field-value">R$ {{ number_format($notaFiscal->valor_iss, 2, ',', '.') }}</div>
                    </div>
                @endif
                @if ($notaFiscal->valor_pis > 0)
                    <div class="field" style="margin-bottom: 0;">
                        <div class="field-label">PIS</div>
                        <div class="field-value">R$ {{ number_format($notaFiscal->valor_pis, 2, ',', '.') }}</div>
                    </div>
                @endif
                @if ($notaFiscal->valor_cofins > 0)
                    <div class="field" style="margin-bottom: 0;">
                        <div class="field-label">COFINS</div>
                        <div class="field-value">R$ {{ number_format($notaFiscal->valor_cofins, 2, ',', '.') }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="field" style="margin-top: 12px;">
            <div class="field-label">VALOR TOTAL DA NOTA</div>
            <div class="field-value large" style="color: {{ $primary }};">
                R$ {{ number_format($notaFiscal->valor_total, 2, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- VINCULAÇÕES -->
    @if ($notaFiscal->ordem_servico_id)
        <div class="section-header">🔗 VINCULAÇÕES</div>
        <div class="section-content">
            <div class="field">
                <div class="field-label">Ordem de Serviço</div>
                <div class="field-value">
                    @php
                        $os = \App\Models\OrdemServico::find($notaFiscal->ordem_servico_id);
                    @endphp
                    {{ $os ? 'OS #' . $os->numero_os : 'OS #' . $notaFiscal->ordem_servico_id }}
                </div>
            </div>
        </div>
    @endif

    <!-- OBSERVAÇÕES E CANCELAMENTO -->
    @if ($notaFiscal->observacoes || $notaFiscal->status === 'cancelada')
        <div class="section-header">📝 {{ $notaFiscal->status === 'cancelada' ? 'CANCELAMENTO' : 'OBSERVAÇÕES' }}</div>
        <div class="section-content">
            @if ($notaFiscal->status === 'cancelada')
                <div class="field">
                    <div class="field-label">Data do Cancelamento</div>
                    <div class="field-value" style="color: #dc2626;">
                        {{ $notaFiscal->data_cancelamento ? \Carbon\Carbon::parse($notaFiscal->data_cancelamento)->format('d/m/Y H:i') : '-' }}
                    </div>
                </div>
                @if ($notaFiscal->motivo_cancelamento)
                    <div class="field">
                        <div class="field-label">Motivo do Cancelamento</div>
                        <div class="observacoes" style="border-left-color: #dc2626;">
                            {{ $notaFiscal->motivo_cancelamento }}
                        </div>
                    </div>
                @endif
            @endif
            
            @if ($notaFiscal->observacoes)
                <div class="observacoes">
                    {{ $notaFiscal->observacoes }}
                </div>
            @endif
        </div>
    @endif

</body>

</html>
