<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro #{{ $financeiro->id }}</title>
    <style>
        @page {
            margin: 0px;
        }

        /* DYNAMIC STYLES */
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
            /* Match Orcamento Padding */
            padding-top: 6.2cm;
            padding-bottom: 3cm;
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
            height: 3.8cm;
            padding-top: 0.5cm;
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: white;
            z-index: 1000;
        }

        /* FOOTER FIXO */
        .footer {
            position: fixed;
            bottom: 0;
            left: 1cm;
            right: 1cm;
            height: 2.5cm;
            padding-bottom: 0.5cm;
            background: white;
            padding-top: 5px;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
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

        .titulo-doc {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .numero-doc {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .section-header {
            background: {{ $primary }};
            color: white;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            width: 120px;
            font-weight: bold;
            color: #4b5563;
        }

        .info-value {
            flex: 1;
            color: #111827;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .status-pago {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #166534;
        }

        .status-pendente {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #854d0e;
        }

        .status-vencido {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #991b1b;
        }

        .tipo-entrada {
            color: #166534;
            font-weight: bold;
        }

        .tipo-saida {
            color: #991b1b;
            font-weight: bold;
        }

        .valores-box {
            background: #eff6ff;
            border: 2px solid {{ $primary }};
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }

        .valor-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .valor-total {
            border-top: 2px solid {{ $primary }};
            padding-top: 8px;
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: {{ $primary }};
        }

        .footer-legal {
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.5;
        }
        
        .footer-warning { 
            background: #fef2f2; 
            border: 1px solid #fecaca; 
            border-radius: 4px; 
            padding: 8px 10px; 
            font-size: 8px; 
            color: #dc2626; 
            text-align: center; 
            margin-bottom: 8px; 
        }
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
            <div class="titulo-doc">RECIBO / DETALHAMENTO</div>
            <div class="numero-doc">#{{ str_pad($financeiro->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div style="font-size: 9px; opacity: 0.9;">
                @if(!empty($financeiro->id_parceiro))
                    <span style="font-weight: bold; color: yellow;">ID Parceiro: {{ $financeiro->id_parceiro }}</span><br>
                @endif
                Emissão: {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-warning">
             Este documento comprova o lançamento financeiro no sistema, mas não substitui Nota Fiscal para fins tributários.
        </div>
        <div class="footer-legal">
            Documento gerado automaticamente pelo sistema.<br>
            <strong>Data da Geração:</strong> {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- CONTEÚDO -->
    <div class="content">
        <!-- DADOS PRINCIPAIS -->
        <div class="section-header">DADOS DA TRANSAÇÃO</div>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">Descrição:</div>
                <div class="info-value">{{ $financeiro->descricao }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo:</div>
                <div class="info-value {{ $financeiro->tipo === 'entrada' ? 'tipo-entrada' : 'tipo-saida' }}">
                    {{ $financeiro->tipo === 'entrada' ? 'RECEITA (ENTRADA)' : 'DESPESA (SAÍDA)' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Categoria:</div>
                <div class="info-value">{{ $financeiro->categoria->nome ?? 'Sem categoria' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $financeiro->status }}">
                        {{ strtoupper($financeiro->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- DADOS DO CLIENTE/FORNECEDOR -->
        @if($financeiro->cadastro)
        <div class="section-header">DADOS DO {{ $financeiro->tipo === 'entrada' ? 'PAGADOR' : 'BENEFICIÁRIO' }}</div>
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value">{{ $financeiro->cadastro->nome }}</div>
            </div>
            @if($financeiro->cadastro->cpf_cnpj)
            <div class="info-row">
                <div class="info-label">CPF/CNPJ:</div>
                <div class="info-value">{{ $financeiro->cadastro->cpf_cnpj }}</div>
            </div>
            @endif
            @if($financeiro->cadastro->email)
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $financeiro->cadastro->email }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- DATAS E VALORES -->
        <div class="section-header">DETALHAMENTO FINANCEIRO</div>
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <div class="info-box">
                    <div class="info-row">
                        <div class="info-label">Data Lançamento:</div>
                        <div class="info-value">{{ $financeiro->data ? $financeiro->data->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Vencimento:</div>
                        <div class="info-value">{{ $financeiro->data_vencimento ? $financeiro->data_vencimento->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Pagamento:</div>
                        <div class="info-value">{{ $financeiro->data_pagamento ? $financeiro->data_pagamento->format('d/m/Y') : 'Pendente' }}</div>
                    </div>
                    @if($financeiro->forma_pagamento)
                    <div class="info-row">
                        <div class="info-label">Forma Pagto:</div>
                        <div class="info-value">{{ ucfirst(str_replace('_', ' ', $financeiro->forma_pagamento)) }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div style="width: 250px;">
                <div class="valores-box">
                    <div class="valor-row">
                        <span>Valor Original:</span>
                        <span>R$ {{ number_format($financeiro->valor, 2, ',', '.') }}</span>
                    </div>
                    @if($financeiro->juros > 0)
                    <div class="valor-row" style="color: #b91c1c;">
                        <span>(+) Juros:</span>
                        <span>R$ {{ number_format($financeiro->juros, 2, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($financeiro->multa > 0)
                    <div class="valor-row" style="color: #b91c1c;">
                        <span>(+) Multa:</span>
                        <span>R$ {{ number_format($financeiro->multa, 2, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($financeiro->desconto > 0)
                    <div class="valor-row" style="color: #15803d;">
                        <span>(-) Desconto:</span>
                        <span>R$ {{ number_format($financeiro->desconto, 2, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    <div class="valor-total">
                        <div style="display: flex; justify-content: space-between;">
                            <span>TOTAL:</span>
                            <span>R$ {{ number_format($financeiro->valor_total ?? $financeiro->valor, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($financeiro->valor_pago > 0)
                    <div style="margin-top: 8px; border-top: 1px dashed #bfdbfe; padding-top: 8px; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; color: #166534; font-weight: bold;">
                            <span>PAGO:</span>
                            <span>R$ {{ number_format($financeiro->valor_pago, 2, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- OBSERVAÇÕES -->
        @if($financeiro->observacoes)
        <div class="section-header">OBSERVAÇÕES</div>
        <div class="info-box">
            <div style="font-size: 10px; color: #374151; white-space: pre-wrap;">{{ $financeiro->observacoes }}</div>
        </div>
        @endif

        <!-- VINCULAÇÕES -->
        @if($financeiro->ordemServico || $financeiro->orcamento)
        <div style="margin-top: 20px; font-size: 9px; color: #6b7280; text-align: center;">
            @if($financeiro->ordemServico)
                Vinculado à OS #{{ $financeiro->ordemServico->numero_os }}
            @endif
            @if($financeiro->ordemServico && $financeiro->orcamento) | @endif
            @if($financeiro->orcamento)
                Vinculado ao Orçamento #{{ $financeiro->orcamento->numero }}
            @endif
        </div>
        @endif
    </div>
</body>
</html>
