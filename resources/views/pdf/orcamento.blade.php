<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $orcamento->numero ?? $orcamento->numero_orcamento }}</title>
    <style>
        @page {
            margin: 0px; /* Reset hard margins to allow absolute positioning from edge */
        }

        /* DYNAMIC STYLES */
        @php
            // Cores
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $secondary = $config->pdf_color_secondary ?? '#eff6ff';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            color: {{ $text }};
            line-height: 1.4;
            
            /* Define content safe area via padding */
            padding-top: 4.5cm;    
            padding-bottom: 2.5cm; 
            padding-left: 1cm;
            padding-right: 1cm;
            margin: 0;
        }

        /* HEADER FIXO - Topo absoluto da página */
        .header {
            position: fixed; 
            top: 0;
            left: 1cm; /* Match the body padding/margin */
            right: 1cm; 
            height: 4cm;
            padding-top: 0.5cm; /* Top Margin visual */
            border-bottom: 3px solid {{ $primary }};
            display: flex;
            background: white; /* Ensure no transparent conflicts */
            z-index: 1000;
        }

        /* FOOTER FIXO - Rodapé absoluto da página */
        .footer {
            position: fixed; 
            bottom: 0;
            left: 1cm; 
            right: 1cm; 
            height: 2cm;
            padding-bottom: 0.5cm; /* Bottom Margin visual */
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

        .numero-orcamento {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .datas {
            font-size: 8px;
            line-height: 1.7;
        }

        /* REST OF STYLES (Unchanged) */
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

        .client-box { padding: 10px 0; border-bottom: 1px solid #e5e7eb; page-break-inside: avoid; }
        .client-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .client-name { font-size: 11px; font-weight: bold; }
        .client-detail { font-size: 9px; color: #6b7280; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table thead { background: #f3f4f6; }
        table thead th { padding: 8px 6px; text-align: left; font-size: 9px; font-weight: 600; color: #374151; border-bottom: 2px solid #d1d5db; }
        table thead th:nth-child(3), table thead th:nth-child(4), table thead th:nth-child(5) { text-align: right; }
        table tbody td { padding: 8px 6px; font-size: 9px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table tbody td:nth-child(3), table tbody td:nth-child(4), table tbody td:nth-child(5) { text-align: right; }

        .item-category { display: inline-block; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 7px; text-transform: uppercase; margin-bottom: 3px; }
        .cat-higienizacao { background: #dbeafe; color: #1e40af; }
        .cat-impermeabilizacao { background: #fef3c7; color: #92400e; }
        .cat-outro { background: #e5e7eb; color: #374151; }
        .item-description { color: #6b7280; font-size: 8px; line-height: 1.3; }

        .valores-section { margin-top: 16px; display: flex; gap: 20px; page-break-inside: avoid; }
        .valores-left { flex: 1; }
        .valores-right { width: 220px; }
        .valores-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
        .valor-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 10px; }
        .valor-row.desconto { color: #dc2626; font-weight: 600; }
        .valor-row.desconto-prestador { color: #ea580c; font-weight: 600; }
        .valor-row-separator { border-top: 2px solid #2563eb; margin: 8px 0; }
        .valor-total-box { background: #eff6ff; border: 2px solid #2563eb; border-radius: 6px; padding: 12px; text-align: center; margin-top: 10px; }
        .valor-total-label { font-size: 11px; color: #1e40af; font-weight: 600; }
        .valor-total-value { font-size: 24px; font-weight: bold; color: #2563eb; }

        .pix-box { background: #ecfdf5; border: 2px solid #10b981; border-radius: 8px; padding: 12px; text-align: center; }
        .pix-title { font-size: 11px; font-weight: bold; color: #065f46; margin-bottom: 8px; }
        .pix-qrcode img { width: 100px; height: 100px; border: 2px solid #10b981; border-radius: 4px; background: white; padding: 3px; }
        .pix-valor { margin-top: 8px; font-size: 12px; font-weight: bold; color: #065f46; }
        .pix-desconto { font-size: 9px; color: #059669; }
        .pix-chave { margin-top: 8px; font-size: 7px; color: #374151; }
        .pix-code { background: white; border: 1px solid #10b981; border-radius: 4px; padding: 5px; font-family: 'Courier New', monospace; font-size: 6px; word-break: break-all; color: #111; line-height: 1.4; margin-top: 5px; }

        .footer-warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 8px 10px; font-size: 8px; color: #dc2626; text-align: center; margin-bottom: 8px; }
        .footer-legal { font-size: 7px; color: #9ca3af; text-align: center; line-height: 1.5; }
    </style>
</head>

<body>
    <!-- LAYOUT LOGIC -->
    @php
        $layout = $config->pdf_layout ?? [];
        if (empty($layout)) {
            $layout = [
                ['type' => 'header', 'data' => ['show_logo' => true, 'show_dates' => true, 'alignment' => 'left']],
                ['type' => 'dados_cliente', 'data' => []],
                ['type' => 'tabela_itens', 'data' => []],
                ['type' => 'container_duplo', 'data' => ['coluna_esquerda' => 'totais', 'coluna_direita' => 'pix']],
                ['type' => 'rodape_padrao', 'data' => []]
            ];
        }

        $headerBlock = collect($layout)->firstWhere('type', 'header');
        $footerBlock = collect($layout)->firstWhere('type', 'rodape_padrao');
        $mainBlocks = collect($layout)->reject(fn($b) => in_array($b['type'], ['header', 'rodape_padrao']));
    @endphp

    <!-- FIXED HEADER -->
    @if($headerBlock)
        @php $data = $headerBlock['data'] ?? []; @endphp
        <div class="header"
            style="justify-content: {{ ($data['alignment'] ?? 'left') === 'center' ? 'center' : 'space-between' }};
                   flex-direction: {{ ($data['alignment'] ?? 'left') === 'center' ? 'column' : 'row' }};
                   align-items: {{ ($data['alignment'] ?? 'left') === 'center' ? 'center' : 'flex-start' }};">
            
            @if(($data['show_logo'] ?? true))
                <div class="header-left" style="{{ ($data['alignment'] ?? 'left') === 'center' ? 'text-align:center; max-width:100%;' : '' }}">
                    @php
                        $logoPath = $config->empresa_logo ?? null;
                        if ($logoPath && !file_exists($logoPath)) $logoPath = storage_path('app/public/' . $logoPath);
                    @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
                    @else
                        <div style="font-size: 16px; font-weight: bold; color: {{ $primary }}; margin-bottom: 8px;">
                            {{ $config->nome_sistema ?? 'Empresa' }}
                        </div>
                    @endif
                    <div class="company-info">
                        {{ $config->empresa_cnpj ?? '' }}<br>
                        {{ $config->empresa_telefone ?? '' }}<br>
                        {{ $config->empresa_email ?? '' }}
                    </div>
                </div>
            @endif

            @if(($data['show_dates'] ?? true))
                <div class="header-right" style="{{ ($data['alignment'] ?? 'left') === 'center' ? 'margin-top:10px; width:100%; text-align:center;' : '' }}">
                    <div style="font-size: 10px; opacity: 0.9;">ORÇAMENTO</div>
                    <div class="numero-orcamento">{{ $orcamento->numero ?? $orcamento->numero_orcamento }}</div>
                    <div class="datas">
                        Emissão: {{ $orcamento->data_orcamento ? \Carbon\Carbon::parse($orcamento->data_orcamento)->format('d/m/Y') : now()->format('d/m/Y') }}<br>
                        Validade: {{ $orcamento->data_validade ? \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') : '' }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- FIXED FOOTER -->
    @if($footerBlock)
        @php $data = $footerBlock['data'] ?? []; @endphp
        <div class="footer">
            <div class="footer-warning">
                ⚠️ {{ $config->pdf_texto_garantia ?? 'Validade 7 dias' }}
            </div>
            <div class="footer-legal">
                {{ $data['texto_legal'] ?? 'Documento não fiscal' }}
            </div>
        </div>
    @endif

    <!-- MAIN CONTENT (Flows inside margins) -->
    <div style="width: 100%;">
        @foreach($mainBlocks as $block)
            @php $data = $block['data'] ?? []; @endphp
            
            <!-- (RENDER BLOCKS - SIMPLIFIED MATCHING TO PREVIOUS CODE) -->
            @if($block['type'] === 'dados_cliente')
                <div class="section-header">{{ $data['titulo'] ?? 'DADOS DO CLIENTE' }}</div>
                <div class="client-box">
                    <span class="client-name">Nome: {{ strtoupper($orcamento->cliente->nome ?? '') }}</span><br>
                    @if(!empty($orcamento->cliente->telefone)) <strong>Tel:</strong> {{ $orcamento->cliente->telefone }} @endif
                    @if(!empty($orcamento->cliente->email)) | <strong>Email:</strong> {{ $orcamento->cliente->email }} @endif
                    @if(!empty($orcamento->cliente->logradouro))
                        <br><strong>End:</strong> {{ $orcamento->cliente->logradouro }}, {{ $orcamento->cliente->numero ?? 'S/N' }} 
                        {{ $orcamento->cliente->bairro ? '- '.$orcamento->cliente->bairro : '' }}
                    @endif
                </div>
            @endif

            @if($block['type'] === 'tabela_itens')
                @php
                    // Agrupa itens por tipo de serviço
                    $itensPorServico = $orcamento->itens->groupBy('servico_tipo');
                @endphp
                
                <div class="section-header">{{ $data['titulo'] ?? 'ITENS DO ORÇAMENTO' }}</div>
                
                @foreach($itensPorServico as $tipoServico => $itens)
                    @php
                        // Usa o ServiceTypeManager para buscar os dados do tipo de serviço
                        $servicoInfo = \App\Services\ServiceTypeManager::get($tipoServico);
                        $servicoLabel = $servicoInfo['label'] ?? ucfirst($tipoServico ?? 'Serviço');
                        $servicoDescricao = $servicoInfo['descricao_pdf'] ?? null;
                        
                        // Cores por tipo
                        $bgColor = match($tipoServico) {
                            'higienizacao' => '#dbeafe',
                            'impermeabilizacao' => '#fef3c7',
                            'combo' => '#d1fae5',
                            default => '#f3f4f6'
                        };
                        $textColor = match($tipoServico) {
                            'higienizacao' => '#1e40af',
                            'impermeabilizacao' => '#92400e',
                            'combo' => '#065f46',
                            default => '#374151'
                        };
                    @endphp
                    
                    {{-- Badge do Serviço em Destaque --}}
                    <div style="margin: 12px 0 8px 0; page-break-inside: avoid;">
                        <span style="display: inline-block; 
                                     background: {{ $bgColor }}; 
                                     color: {{ $textColor }}; 
                                     padding: 6px 14px; 
                                     border-radius: 16px; 
                                     font-weight: bold; 
                                     font-size: 11px;
                                     text-transform: uppercase;
                                     border: 2px solid {{ $textColor }};">
                            {{ $servicoLabel }}
                        </span>
                        @if($servicoDescricao)
                            <span style="display: inline-block; 
                                         margin-left: 10px;
                                         font-size: 9px; 
                                         color: #6b7280;
                                         font-style: italic;">
                                {{ $servicoDescricao }}
                            </span>
                        @endif
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th width="50%">DESCRIÇÃO</th>
                                <th width="10%">UN</th>
                                <th width="10%">QTD</th>
                                <th width="15%" style="text-align:right">VALOR UNIT.</th>
                                <th width="15%" style="text-align:right">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itens as $item)
                            <tr>
                                <td>
                                    <div class="item-description">
                                        <strong>{{ $item->item_nome }}</strong>
                                        @if($item->descricao && $item->descricao !== $item->item_nome) 
                                            <br><span style="color: #6b7280;">{{ $item->descricao }}</span> 
                                        @endif
                                    </div>
                                </td>
                                <td>{{ strtoupper($item->unidade ?? 'UN') }}</td>
                                <td>{{ number_format($item->quantidade, $item->quantidade == intval($item->quantidade) ? 0 : 2, ',', '.') }}</td>
                                <td style="text-align:right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                <td style="text-align:right"><strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
                
                {{-- Extras (Nicho) --}}
                @if($orcamento->extra_attributes)
                    @foreach($orcamento->extra_attributes as $key => $val)
                        @if(is_numeric($val) && $val > 0)
                        <table style="margin-top: 5px;">
                            <tr>
                                <td width="85%" style="text-align:right; border-bottom: 1px dashed #ddd;"><strong>{{ ucfirst($key) }}</strong></td>
                                <td width="15%" style="text-align:right; border-bottom: 1px dashed #ddd;"><strong>R$ {{ number_format($val, 2, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                        @endif
                    @endforeach
                @endif
            @endif

            @if($block['type'] === 'galeria_fotos')
                 @php
                    $showPhotos = $orcamento->pdf_mostrar_fotos ?? true;
                    $colArq = $orcamento->getMedia('arquivos');
                    $colFotos = $orcamento->getMedia('fotos_orcamento');
                    $allMedia = $colArq->merge($colFotos);
                    $images = $allMedia->filter(fn ($media) => str_starts_with($media->mime_type, 'image/'));
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
                    $cols = (int) ($data['columns'] ?? 2);
                 @endphp
                 @if($showPhotos && count($fotos) > 0)
                    <div class="section-header" style="page-break-inside: avoid;">{{ $data['titulo'] ?? 'REGISTROS FOTOGRÁFICOS' }}</div>
                    <div class="fotos-grid" style="display: grid; grid-template-columns: repeat({{ $cols }}, 1fr); gap: 10px; margin-bottom: 20px; page-break-inside: avoid;">
                        @foreach($fotos as $foto)
                            @if($foto->base64_src)
                            <div class="foto-item" style="text-align: center; page-break-inside: avoid;">
                                 <img src="{{ $foto->base64_src }}" style="width: 100%; height: auto; border-radius: 4px; border: 1px solid #eee;">
                                 @if(($data['show_legend'] ?? false)) <div style="font-size: 8px; color: #999;">{{ $foto->file_name }}</div> @endif
                            </div>
                            @endif
                        @endforeach
                    </div>
                 @endif
            @endif

            @if($block['type'] === 'container_duplo')
                <div class="valores-section" style="page-break-inside: avoid;">
                    <div class="valores-left" style="flex:1; margin-right: 15px;">
                        @include('pdf.partials.block_content', ['type' => $data['coluna_esquerda'] ?? 'totais', 'orcamento' => $orcamento, 'config' => $config])
                    </div>
                    <div class="valores-right" style="flex:1;">
                        @include('pdf.partials.block_content', ['type' => $data['coluna_direita'] ?? 'pix', 'orcamento' => $orcamento, 'config' => $config])
                    </div>
                </div>
            @endif

            @if($block['type'] === 'texto_livre')
                <div style="margin: 20px 0; page-break-inside: avoid;">{!! $data['conteudo'] ?? '' !!}</div>
            @endif
            
            @if($block['type'] === 'linha_separadora')
                <hr style="border: 0; border-top: {{ $data['espessura'] ?? '1px' }} solid {{ $data['cor'] ?? '#eee' }}; margin: 20px 0;">
            @endif

        @endforeach
    </div>