<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $os->numero_os ?? $os->numero_os }}</title>
    <style>
$style
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
                    <div class="numero-orcamento">{{ $os->numero_os ?? $os->numero_os }}</div>
                    <div class="datas">
                        @if(!empty($os->id_parceiro))
                            <span style="font-weight: bold; color: yellow;">ID Parceiro: {{ $os->id_parceiro }}</span><br>
                        @endif
                        Emissão: {{ $os->data_prevista ? \Carbon\Carbon::parse($os->data_prevista)->format('d/m/Y') : now()->format('d/m/Y') }}<br>
                        Validade: {{ $garantia->data_fim ? \Carbon\Carbon::parse($garantia->data_fim)->format('d/m/Y') : '' }}
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
                {{ $data['texto_legal'] ?? 'Documento não fiscal' }}<br>
                <strong>Certificado de Geração:</strong> {{ now()->format('d/m/Y H:i:s') }} | Orçamento #{{ $os->numero_os }}
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
                <div class="client-box" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; padding: 12px 0;">
                    {{-- Nome completo ocupa as 2 colunas --}}
                    <div style="grid-column: 1 / -1;">
                        <span class="client-name">{{ strtoupper($os->cliente->nome ?? '') }}</span>
                    </div>
                    
                    {{-- Telefone --}}
                    @if(!empty($os->cliente->telefone))
                    <div style="font-size: 9px;">
                        <strong style="color: #6b7280;">📞 Telefone:</strong><br>
                        <span style="color: #111827;">{{ $os->cliente->telefone }}</span>
                    </div>
                    @endif
                    
                    {{-- Email --}}
                    @if(!empty($os->cliente->email))
                    <div style="font-size: 9px;">
                        <strong style="color: #6b7280;">✉️ Email:</strong><br>
                        <span style="color: #111827;">{{ $os->cliente->email }}</span>
                    </div>
                    @endif
                    
                    {{-- Endereço completo ocupa as 2 colunas --}}
                    @if(!empty($os->cliente->logradouro))
                    <div style="grid-column: 1 / -1; font-size: 9px; margin-top: 4px;">
                        <strong style="color: #6b7280;">📍 Endereço:</strong><br>
                        <span style="color: #111827;">
                            {{ $os->cliente->logradouro }}, {{ $os->cliente->numero ?? 'S/N' }}
                            @if(!empty($os->cliente->complemento)) - {{ $os->cliente->complemento }}@endif
                            @if(!empty($os->cliente->bairro))<br>{{ $os->cliente->bairro }}@endif
                            @if(!empty($os->cliente->cidade)) - {{ $os->cliente->cidade }}@endif
                            @if(!empty($os->cliente->estado))/{{ $os->cliente->estado }}@endif
                            @if(!empty($os->cliente->cep)) - CEP: {{ $os->cliente->cep }}@endif
                        </span>
                    </div>
                    @endif
                    
                    {{-- Documento (CPF/CNPJ) --}}
                    @if(!empty($os->cliente->documento))
                    <div style="font-size: 9px;">
                        <strong style="color: #6b7280;">🆔 Documento:</strong><br>
                        <span style="color: #111827;">{{ $os->cliente->documento }}</span>
                    </div>
                    @endif
                </div>
            @endif

            @if($block['type'] === 'tabela_itens')
                @php
                    // Agrupa itens por tipo de serviço
                    $itensPorServico = ($orcamento ? $orcamento->itens : collect())->groupBy('servico_tipo');
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
                        
                        {{-- Show Warranty if configured --}}
                        @php
                            $diasGarantia = \App\Services\ServiceTypeManager::getDiasGarantia($tipoServico);
                        @endphp
                        @if($diasGarantia)
                            <span style="display: inline-block;
                                         background: #ecfdf5;
                                         color: #047857;
                                         padding: 6px 10px;
                                         border-radius: 16px;
                                         font-weight: bold;
                                         font-size: 10px;
                                         margin-left: 5px;
                                         border: 1px solid #047857;">
                                🛡️ Garantia: {{ $diasGarantia }} dias
                            </span>
                        @endif

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
                @if($orcamento?->extra_attributes)
                    @foreach($orcamento?->extra_attributes as $key => $val)
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
                    $showPhotos = $orcamento?->pdf_mostrar_fotos ?? true;
                    $colArq = ($orcamento ? $orcamento->getMedia('arquivos') : collect());
                    $colFotos = ($orcamento ? $orcamento->getMedia('fotos_orcamento') : collect());
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
                {{-- TERMOS DA GARANTIA (SUBSTITUI TOTAIS E PIX) --}}
                <div style="margin-top: 20px; page-break-inside: avoid;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div style="display: inline-block; padding: 10px 20px; background-color: #fef3c7; color: #d97706; border: 2px solid #f59e0b; border-radius: 5px; font-weight: bold; font-size: 14px; letter-spacing: 1px;">
                            GARANTIA DE {{ $garantia->dias_garantia ?? 0 }} DIAS
                        </div>
                    </div>
                    <div class="section-header">TERMOS DA GARANTIA</div>
                    <div style="background: #f9fafb; padding: 15px; border-radius: 8px; font-size: 10px; color: #374151; line-height: 1.6; border: 1px solid #e5e7eb;">
                        @if($garantia->observacoes)
                            {!! nl2br(e($garantia->observacoes)) !!}
                        @else
                            <p>Estão garantidos os serviços realizados conforme as especificações técnicas, desde que observadas as condições adequadas de uso e manutenção.</p>
                            <p>A garantia não cobre defeitos ocasionados por mau uso, agentes externos, produtos químicos não recomendados, ou problemas decorrentes do próprio desgaste natural dos materiais.</p>
                        @endif
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
