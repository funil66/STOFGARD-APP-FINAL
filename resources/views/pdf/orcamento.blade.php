<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orcamento {{ $orcamento->numero_orcamento }}</title>
    <style>
        @page { size: A4; margin: 20mm; }
        :root { --brand: #004aad; --accent: #f59e0b; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; margin: 0; }

        /* Header centered as in the sample PDF */
        .header { text-align: center; margin-bottom: 18px; }
        .logo { max-width: 180px; height: auto; display: block; margin: 0 auto 6px; }
        .company-name { font-size: 18px; font-weight: 800; color: var(--brand); letter-spacing: 1px; margin: 0; }
        .company-details { font-size: 9px; color: #555; margin-top: 4px; }

        .meta { width: 100%; margin-bottom: 8px; border-bottom: 2px solid var(--brand); padding-bottom: 8px; }
        .meta .left { text-align: left; display:inline-block; width: 65%; vertical-align: top; }
        .meta .right { text-align: right; display:inline-block; width: 34%; vertical-align: top; }
        .meta .meta-title { font-weight: 700; color: var(--brand); font-size: 12px; }
        .meta small { color: #666; font-size: 9px; }

        .section-title { background-color: #f6f8fb; color: var(--brand); font-weight: 800; padding: 6px 10px; border-left: 6px solid var(--brand); margin-top: 14px; margin-bottom: 6px; font-size: 11px; }

        .client-table td { padding: 2px 0; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items th { background: var(--brand); color: #fff; padding: 8px; font-size: 9px; text-transform: uppercase; }
        table.items td { padding: 8px; border-bottom: 1px solid #e6e6e6; font-size: 9px; }
        .category-header { background:#fafafa; font-weight:700; padding:8px; border-bottom:1px solid #eee; }

        .totals { width: 40%; float: right; border-collapse: collapse; margin-top: 8px; }
        .totals td { padding: 6px; text-align: right; font-size: 10px; }
        .totals .final { font-size: 16px; font-weight: 800; color: var(--brand); border-top: 2px solid var(--brand); padding-top: 10px; }

        .pix { clear: both; margin-top: 18px; border: 1px solid #e6e6e6; padding: 12px; background:#fcfcfc; }
        .pix .code { font-family: 'Courier New', monospace; background: #f3f4f6; padding: 8px; word-break:break-all; border:1px solid #e6e6e6; }
        .pix .qr { text-align: center; }

        .observacoes { background: #fff8e1; border-left: 6px solid var(--accent); padding: 12px; margin-top: 12px; font-size: 10px; }

        .footer { margin-top: 18px; border-top: 1px solid #eee; text-align: center; color:#777; font-size:9px; padding-top:8px; }

        /* small helpers */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-6 { margin-bottom: 6px; }

        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('images/logo-stofgard.png')))
            <img src="{{ public_path('images/logo-stofgard.png') }}" class="logo" alt="Logo">
        @endif
        <div class="company-name">STOFGARD</div>
        <div class="company-details">Higienização & Impermeabilização — CNPJ: 58.794.846/0001-20 | (16) 99104-0195 | contato@stofgard.com.br</div>
    </div>

    <div class="meta">
        <div class="left">
            <div class="section-title" style="display:inline-block;">DADOS DO CLIENTE</div>
        </div>
        <div class="right">
            <div class="meta-title">{{ $orcamento->numero_orcamento }}</div>
            <div class="mb-6"><small>Emissão: {{ $orcamento->data_orcamento->format('d/m/Y H:i') }}</small></div>
            <div><small><strong>Válido até: {{ $orcamento->data_validade->format('d/m/Y') }}</strong></small></div>
        </div>
    </div>

    <table class="client-table" style="width:100%; margin-bottom:8px;">
        <tr>
            <td style="width: 18%; font-weight:700">Cliente:</td>
            <td>{{ $orcamento->cliente->nome }}</td>
        </tr>
        @if($orcamento->cliente->email)
        <tr>
            <td style="font-weight:700">E-mail:</td>
            <td>{{ $orcamento->cliente->email }}</td>
        </tr>
        @endif
        @if($orcamento->cliente->endereco)
        <tr>
            <td style="font-weight:700">Endereço:</td>
            <td>{{ $orcamento->cliente->endereco }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">ITENS DO ORÇAMENTO</div>

    @php
        $itensHigi = $orcamento->itens->filter(fn($i)=> $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'higienizacao');
        $itensImper = $orcamento->itens->filter(fn($i)=> $i->tabelaPreco && $i->tabelaPreco->tipo_servico === 'impermeabilizacao');
        $itensSemTipo = $orcamento->itens->filter(fn($i)=> !$i->tabelaPreco);
    @endphp

    @if($itensHigi->count())
        <div class="category-header">HIGIENIZAÇÃO</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:55%">Descrição</th>
                    <th style="width:10%" class="text-center">UN</th>
                    <th style="width:10%" class="text-center">Qtd</th>
                    <th style="width:12%" class="text-right">Valor Un.</th>
                    <th style="width:13%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensHigi as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-center">{{ number_format($item->quantidade,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal,2,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($itensImper->count())
        <div class="category-header">IMPERMEABILIZAÇÃO</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-center">UN</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-right">Valor Un.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach($itensImper as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-center">{{ number_format($item->quantidade,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal,2,',','.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($itensSemTipo->count())
        <div class="category-header">OUTROS ITENS</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-center">UN</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-right">Valor Un.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach($itensSemTipo as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-center">{{ number_format($item->quantidade,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario,2,',','.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal,2,',','.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <table class="totals">
        <tr><td>Subtotal:</td><td>R$ {{ number_format($orcamento->valor_subtotal,2,',','.') }}</td></tr>
        @if($orcamento->valor_desconto > 0)
            <tr><td>Desconto PIX:</td><td>- R$ {{ number_format($orcamento->valor_desconto,2,',','.') }}</td></tr>
        @endif
        @php $valorAfterDiscount = $orcamento->valor_subtotal - $orcamento->valor_desconto; @endphp
        <tr class="final"><td>VALOR TOTAL:</td><td>R$ {{ number_format($orcamento->valor_total,2,',','.') }}</td></tr>
    </table>

    <div class="pix">
        <div style="display:flex; gap:12px; align-items:center;">
            <div style="flex:1">
                <div style="font-weight:700;color: #059669">PIX (informações)</div>
                @if(($qrCodePix ?? null) || $orcamento->pix_qrcode_base64)
                    <div style="margin-top:6px;"><strong>Chave:</strong> <span style="font-family:monospace">{{ $orcamento->pix_chave_valor ?? '—' }}</span></div>
                    <div style="margin-top:6px;"><strong>Valor:</strong> R$ {{ number_format($valorAfterDiscount ?? $orcamento->valor_total,2,',','.') }}</div>
                    @if($payload = ($copiaCopia ?? null))
                        <div style="margin-top:8px;font-size:9px"><strong>Copia e Cola:</strong>
                            <div class="code">{{ $payload }}</div>
                        </div>
                    @endif
                @else
                    <div style="color:#666">Nenhum QR salvo.</div>
                @endif
            </div>
            <div style="width:140px; text-align:center;">
                @if(($qrCodePix ?? null) || $orcamento->pix_qrcode_base64)
                    <img src="{{ $qrCodePix ?? $orcamento->pix_qrcode_base64 }}" alt="QR" style="max-width:120px;border:1px solid #e6e6e6;padding:6px;background:white;">
                @endif
            </div>
        </div>
    </div>

    @if($orcamento->observacoes)
        <div class="observacoes">{!! nl2br(e($orcamento->observacoes)) !!}</div>
    @endif

    <div class="footer">Validade: Orçamento e QR Code PIX válidos por 7 dias a partir da emissão. Documento gerado em {{ now()->format('d/m/Y H:i:s') }} @if($orcamento->user) por {{ $orcamento->user->name }} @endif</div>

</body>
</html>
    <!-- DADOS DO CLIENTE -->
    <div class="section-title">DADOS DO CLIENTE</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div class="info-value">{{ $orcamento->cliente->nome }}</div>
            <div class="info-label">Telefone:</div>
            <div class="info-value">{{ $orcamento->cliente->celular }}</div>
        </div>
        @if($orcamento->cliente->email)
        <div class="info-row">
            <div class="info-label">E-mail:</div>
            <div class="info-value" style="width: 75%;">{{ $orcamento->cliente->email }}</div>
        </div>
        @endif
        @if($orcamento->cliente->endereco)
        <div class="info-row">
            <div class="info-label">Endereço:</div>
            <div class="info-value" style="width: 75%;">{{ $orcamento->cliente->endereco }}</div>
        </div>
        @endif
    </div>

    <!-- ITENS DO ORÇAMENTO -->
    <div class="section-title">ITENS DO ORÇAMENTO</div>
    
    @php
        $itensHigi = $orcamento->itens->filter(function($item) {
            return $item->tabelaPreco && $item->tabelaPreco->tipo_servico === 'higienizacao';
        });
        $itensImper = $orcamento->itens->filter(function($item) {
            return $item->tabelaPreco && $item->tabelaPreco->tipo_servico === 'impermeabilizacao';
        });
        $itensSemTipo = $orcamento->itens->filter(function($item) {
            return !$item->tabelaPreco;
        });
    @endphp

    @if($itensHigi->count() > 0)
        <div style="display:flex;align-items:flex-start;gap:8px;margin-top:8px;margin-bottom:6px;">
            <div style="min-width:120px;flex-shrink:0;">
                <span class="btn-badge bg-higi">HIGIENIZAÇÃO</span>
            </div>
            <div style="flex:1;font-size:10.6px;color:#333;line-height:1.15">Biossanitização Profunda: Extração de alta pressão para eliminação de biofilmes, ácaros e bactérias, garantindo assepsia total das fibras e neutralização de odores.</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Descrição</th>
                    <th style="width: 10%;" class="text-center">Unid.</th>
                    <th style="width: 10%;" class="text-right">Qtd.</th>
                    <th style="width: 15%;" class="text-right">Valor Unit.</th>
                    <th style="width: 15%;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensHigi as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($itensImper->count() > 0)
        <div style="display:flex;align-items:flex-start;gap:8px;margin-top:8px;margin-bottom:6px;">
            <div style="min-width:120px;flex-shrink:0;">
                <span class="btn-badge bg-imper">IMPERMEABILIZAÇÃO</span>
            </div>
            <div style="flex:1;font-size:10.6px;color:#333;line-height:1.15">Escudo hidrofóbico invisível que repele líquidos e óleos, preservando a integridade das fibras e prolongando a vida útil do tecido.</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Descrição</th>
                    <th style="width: 10%;" class="text-center">Unid.</th>
                    <th style="width: 10%;" class="text-right">Qtd.</th>
                    <th style="width: 15%;" class="text-right">Valor Unit.</th>
                    <th style="width: 15%;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensImper as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($itensSemTipo->count() > 0)
        <h3 style="font-size: 12px; margin-top: 15px; margin-bottom: 8px;">OUTROS ITENS</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Descrição</th>
                    <th style="width: 10%;" class="text-center">Unid.</th>
                    <th style="width: 10%;" class="text-right">Qtd.</th>
                    <th style="width: 15%;" class="text-right">Valor Unit.</th>
                    <th style="width: 15%;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensSemTipo as $item)
                <tr>
                    <td>{{ $item->descricao_item }}</td>
                    <td class="text-center">{{ $item->unidade_medida === 'm2' ? 'M²' : 'UN' }}</td>
                    <td class="text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TOTAIS E PIX (lado a lado) -->
    <div class="clearfix" style="display:flex;gap:12px;align-items:flex-start;">
        <div class="totals-section" style="flex:1;">

            {{-- Detailed numeric breakdown (subtotal, commissions, discounts, per-method values, final) --}}
            <div style="padding:10px;background:#fff;border-radius:6px;border:1px solid #eef2f6">
                <div style="font-weight:700;color:var(--brand);margin-bottom:6px">Resumo de Valores</div>

                <div class="total-row"><div class="total-label">Subtotal:</div><div class="total-value">R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</div></div>

                @php
                    $comissaoLabel = null;
                    $comissaoValor = null;
                    if ($orcamento->parceiro) {
                        $percent = $orcamento->parceiro->percentual_comissao ?? null;
                        if ($percent) {
                            // comissão baseada no subtotal (mais adequado quando desconto afeta total final)
                            $comissaoValor = round(($orcamento->valor_subtotal * $percent) / 100, 2);
                            $comissaoLabel = sprintf('Comissão Parceiro (%s%%):', $percent);
                        }
                    }
                @endphp

                @if($comissaoValor)
                    <div class="total-row"><div class="total-label">{{ $comissaoLabel }}</div><div class="total-value">R$ {{ number_format($comissaoValor, 2, ',', '.') }}</div></div>
                @endif

                @if($orcamento->valor_desconto > 0)
                    <div class="total-row"><div class="total-label">Desconto PIX:</div><div class="total-value" style="color:var(--accent)">- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</div></div>
                @endif

                {{-- Amount by method: show PIX amount when applicable (after discounts) --}}
                @php
                    $valorAfterDiscount = $orcamento->valor_subtotal - $orcamento->valor_desconto;
                    $valorPix = $orcamento->forma_pagamento === 'pix' ? $valorAfterDiscount : null;
                @endphp

                @if($valorPix)
                    <div class="total-row"><div class="total-label">Valor (PIX):</div><div class="total-value">R$ {{ number_format($valorPix, 2, ',', '.') }}</div></div>
                @endif

                {{-- Final total (logical order) --}}
                <div class="total-row total-final" style="margin-top:8px"><div class="total-label">VALOR TOTAL:</div><div class="total-value">R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</div></div>

            </div>

            {{-- Payment method note --}}
            @if($orcamento->forma_pagamento)
            <div style="margin-top:10px; font-size:11px; color:#333">Forma de Pagamento: <strong>{{ match($orcamento->forma_pagamento) {
                        'pix' => 'PIX',
                        'dinheiro' => 'Dinheiro',
                        'cartao_credito' => 'Cartao de Credito',
                        'cartao_debito' => 'Cartao de Debito',
                        'boleto' => 'Boleto',
                        'transferencia' => 'Transferencia',
                        default => $orcamento->forma_pagamento
                    } }}</strong>
                    @if($orcamento->desconto_pix_aplicado)
                        <span style="color: #059669; font-weight: bold; margin-left: 10px;">Desconto de 10% aplicado</span>
                    @endif
            </div>
            @endif

        </div>

        {{-- Compact PIX box integrated into totals as accessory --}}
        <div style="width:260px;flex-shrink:0">
            <div class="pix-box">
                <div style="font-weight:700;color:#059669;margin-bottom:8px">PIX (informações)</div>
                @if(($qrCodePix ?? null) || $orcamento->pix_qrcode_base64)
                <div style="display:flex;gap:8px;align-items:center">
                    <div style="flex-shrink:0"><img src="{{ $qrCodePix ?? $orcamento->pix_qrcode_base64 }}" style="width:64px;height:64px;border:1px solid #ddd;padding:2px;background:white"></div>
                    <div style="font-size:9px">
                        <div style="font-weight:700;color:#111">Chave</div>
                        <div style="color:#666;">@if($orcamento->pix_chave_tipo==='cnpj') CNPJ @elseif($orcamento->pix_chave_tipo==='telefone') Tel @else Chave @endif: <span style="font-family:monospace">{{ $orcamento->pix_chave_valor ?? '—' }}</span></div>
                        <div style="margin-top:6px;font-weight:700;color:#111">Valor</div>
                        <div>R$ {{ number_format($valorAfterDiscount ?? $orcamento->valor_total, 2, ',', '.') }}</div>
                    </div>
                </div>
                @else
                    <div style="font-size:9px;color:#666">Nenhum QR salvo.</div>
                @endif
            </div>
        </div>

    </div>

    @php
        // Verificar se algum item é de impermeabilização
        $temImpermeabilizacao = $orcamento->itens->contains(function($item) {
            return $item->tabelaPreco && $item->tabelaPreco->tipo_servico === 'impermeabilizacao';
        });
    @endphp



    <!-- OBSERVACOES -->
    @if($orcamento->observacoes)
    <div style="clear: both; margin-top: 25px;">
        <div class="section-title">OBSERVACOES</div>
        <div class="observacoes">
            {!! nl2br(e($orcamento->observacoes)) !!}
        </div>
    </div>
    @endif



    <!-- VALIDADE -->



    <!-- FOOTER (simplified) -->
    <div class="footer" style="position:fixed;bottom:4mm;left:0;right:0;padding:0 10mm;box-sizing:border-box;">
        <p style="text-align: center; font-size: 9px; color: #999; margin-top: 10px;">
            <span style="color:#d97706;font-weight:700;display:block;margin-bottom:6px">Validade: Orçamento e QR Code PIX válidos por 7 dias a partir da emissão.</span>
            Este documento não representa um contrato firmado. Após a aprovação do orçamento, será gerada uma Ordem de Serviço oficial.<br>
            Documento gerado em {{ \Carbon\Carbon::now('America/Sao_Paulo')->format('d/m/Y H:i:s') }}
            @if($orcamento->user)
                por {{ $orcamento->user->name }}
            @endif
        </p>
    </div>
  </div>
</div>
