<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Cadastral - {{ $cadastro->nome }}</title>
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

        /* SECTION HEADER - PADRÃO DO ORÇAMENTO */
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

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
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

        .badge-cliente { background-color: #0ea5e9; }
        .badge-loja { background-color: #8b5cf6; }
        .badge-vendedor { background-color: #f59e0b; }
        .badge-arquiteto { background-color: #10b981; }
        .badge-parceiro { background-color: #6366f1; }

        /* TABELA PADRÃO */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table thead {
            background: #f3f4f6;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }

        table tbody td {
            padding: 8px 6px;
            font-size: 9px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

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

        .resumo-total-box {
            background: {{ $secondary }};
            border: 2px solid {{ $primary }};
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            margin-top: 10px;
        }

        .resumo-total-label {
            font-size: 11px;
            color: #1e40af;
            font-weight: 600;
        }

        .resumo-total-value {
            font-size: 20px;
            font-weight: bold;
            color: {{ $primary }};
        }

        /* GALERIA DE FOTOS */
        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .foto-item {
            text-align: center;
        }

        .foto-item img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid #eee;
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
            <div style="font-size: 10px; opacity: 0.9;">FICHA CADASTRAL</div>
            <div class="doc-number">#{{ str_pad($cadastro->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="doc-meta">
                Data: {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Tipo: {{ strtoupper($cadastro->tipo) }}
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

        <!-- INFORMAÇÕES GERAIS (AGRUPADAS) -->
        <div class="section-header">INFORMAÇÕES GERAIS</div>
        <div class="info-box">
            <div class="info-grid">
                <!-- IDENTIFICAÇÃO -->
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">Nome / Razão Social</span>
                        <span class="value" style="font-size: 13px; font-weight: bold;">{{ strtoupper($cadastro->nome) }}</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Tipo de Cadastro</span>
                        <span class="badge badge-{{ $cadastro->tipo }}">{{ $cadastro->tipo }}</span>
                    </div>
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Cadastrado em</span>
                        <span class="value">{{ \Carbon\Carbon::parse($cadastro->created_at)->format('d/m/Y') }}</span>
                    </div>
                </div>
                
                <!-- DOCUMENTOS -->
                @if($cadastro->documento || $cadastro->rg_ie)
                <div class="info-grid-row">
                    @if($cadastro->documento)
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">CPF / CNPJ</span>
                        <span class="value">{{ $cadastro->documento }}</span>
                    </div>
                    @endif
                    @if($cadastro->rg_ie)
                    <div class="info-cell" style="width: 50%;">
                        <span class="label">RG / Inscrição Estadual</span>
                        <span class="value">{{ $cadastro->rg_ie }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <!-- CONTATO -->
                @if($cadastro->telefone || $cadastro->celular || $cadastro->telefone_fixo || $cadastro->email)
                <div class="info-grid-row">
                    @if($cadastro->telefone || $cadastro->celular)
                    <div class="info-cell" style="width: 33%;">
                        <span class="label">WhatsApp / Celular</span>
                        <span class="value">{{ $cadastro->telefone ?? $cadastro->celular }}</span>
                    </div>
                    @endif
                    @if($cadastro->telefone_fixo)
                    <div class="info-cell" style="width: 33%;">
                        <span class="label">Telefone Fixo</span>
                        <span class="value">{{ $cadastro->telefone_fixo }}</span>
                    </div>
                    @endif
                    @if($cadastro->email)
                    <div class="info-cell" style="width: 34%;">
                        <span class="label">E-mail</span>
                        <span class="value">{{ $cadastro->email }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <!-- ENDEREÇO -->
                @if($cadastro->logradouro || $cadastro->cidade || $cadastro->cep)
                <div class="info-grid-row">
                    @if($cadastro->logradouro)
                    <div class="info-cell" style="width: 60%;">
                        <span class="label">Logradouro</span>
                        <span class="value">
                            {{ $cadastro->logradouro }}
                            @if($cadastro->numero), {{ $cadastro->numero }}@endif
                            @if($cadastro->complemento) - {{ $cadastro->complemento }}@endif
                        </span>
                    </div>
                    @endif
                    @if($cadastro->bairro)
                    <div class="info-cell" style="width: 25%;">
                        <span class="label">Bairro</span>
                        <span class="value">{{ $cadastro->bairro }}</span>
                    </div>
                    @endif
                    @if($cadastro->cep)
                    <div class="info-cell" style="width: 15%;">
                        <span class="label">CEP</span>
                        <span class="value">{{ $cadastro->cep }}</span>
                    </div>
                    @endif
                </div>
                @if($cadastro->cidade || $cadastro->estado)
                <div class="info-grid-row">
                    <div class="info-cell" style="width: 60%;">
                        <span class="label">Cidade / UF</span>
                        <span class="value">{{ $cadastro->cidade }} / {{ $cadastro->estado }}</span>
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- DADOS FINANCEIROS (Para Lojas, Vendedores, Arquitetos) -->
        @if(in_array($cadastro->tipo, ['loja', 'vendedor', 'arquiteto', 'parceiro']))
            <div class="section-header">DADOS FINANCEIROS</div>
            <div class="info-box">
                <div class="info-grid">
                    <div class="info-grid-row">
                        <div class="info-cell" style="width: 25%;">
                            <span class="label">Comissão (%)</span>
                            <span class="value" style="font-size: 14px; font-weight: bold; color: {{ $primary }};">
                                {{ number_format($cadastro->comissao_percentual ?? 0, 2, ',', '.') }}%
                            </span>
                        </div>
                        <div class="info-cell" style="width: 75%;">
                            <span class="label">Chave PIX</span>
                            <span class="value">{{ $cadastro->chave_pix ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- VÍNCULOS (Para Vendedores) -->
        @if($cadastro->tipo === 'vendedor' && $cadastro->loja)
            <div class="section-header">VÍNCULOS COMERCIAIS</div>
            <div class="info-box">
                <div class="info-grid">
                    <div class="info-grid-row">
                        <div class="info-cell" style="width: 50%;">
                            <span class="label">Loja Vinculada</span>
                            <span class="value" style="font-weight: bold;">{{ $cadastro->loja->nome }}</span>
                        </div>
                        <div class="info-cell" style="width: 50%;">
                            <span class="label">CNPJ da Loja</span>
                            <span class="value">{{ $cadastro->loja->documento ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- RESUMO FINANCEIRO (Para Clientes) -->
        @if($cadastro->tipo === 'cliente')
            <div class="section-header">RESUMO FINANCEIRO</div>
            <div style="display: flex; gap: 15px; page-break-inside: avoid;">
                <div class="resumo-box" style="flex: 1;">
                    <div class="resumo-row">
                        <span>Total Recebido:</span>
                        <span style="color: #10b981; font-weight: bold;">R$ {{ number_format($cadastro->total_receitas ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <div class="resumo-row">
                        <span>Pendente a Receber:</span>
                        <span style="color: #f59e0b; font-weight: bold;">R$ {{ number_format($cadastro->pendentes_receber ?? 0, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="resumo-box" style="flex: 1;">
                    <div class="resumo-row">
                        <span>Orçamentos Aprovados:</span>
                        <span style="font-weight: bold;">{{ $cadastro->orcamentos_aprovados_count ?? 0 }}</span>
                    </div>
                    <div class="resumo-row">
                        <span>OS Concluídas:</span>
                        <span style="font-weight: bold;">{{ $cadastro->os_concluidas_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- GALERIA DE DOCUMENTOS -->
        @php
            $showDocuments = $cadastro->pdf_mostrar_documentos ?? true;
            $mediaItems = $cadastro->getMedia('arquivos');
            $images = $mediaItems->filter(fn ($media) => str_starts_with($media->mime_type, 'image/'));
            $fotos = $images->map(function($media) {
                try {
                    $path = $media->getPath();
                    if (file_exists($path)) {
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        if ($data) {
                            $media->base64_src = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            return $media;
                        }
                    }
                } catch (\Exception $e) {}
                return null;
            })->filter();
        @endphp

        @if($showDocuments && $fotos->count() > 0)
            <div class="section-header">GALERIA DE DOCUMENTOS</div>
            <div class="fotos-grid" style="page-break-inside: avoid;">
                @foreach($fotos as $foto)
                    @if($foto->base64_src)
                        <div class="foto-item">
                            <img src="{{ $foto->base64_src }}" alt="{{ $foto->file_name }}">
                            <div style="font-size: 7px; color: #999; margin-top: 3px;">{{ $foto->file_name }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

    </div>
</body>

</html>
