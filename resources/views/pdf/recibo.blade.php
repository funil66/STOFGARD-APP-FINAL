<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        @page {
            margin: 0px;
        }

        @php
            $primary = $config->pdf_color_primary ?? '#2563eb';
            $text = $config->pdf_color_text ?? '#1f2937';
        @endphp

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica', Arial, sans-serif;
            font-s
i               ze: 10px;

                        color: {{ $text }};
            line-height: 1.5;
            padding: 1.5cm 1.5cm 2cm;
            margin: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

               
                        border-bottom: 3px solid {{ $primary }};
            padding-bottom: 15px;
            margin-bottom: 25px;
        }


           
                      
       

        .logo-img { max
-           width: 180px; m
a           x-height: 60px;
        margin-bottom: 5px; }
        .company-info { font-size: 8px; color: #374151; }

        .header-title {

            

                                      text-align: right; color: {{ $primary }};

        }

       
           
           
           
           
        .doc-title 
{            font-size: 22p
x           ; font-weight
:        bold; text-transform: uppercase; margin-bottom: 4px; }
        .doc-date { font-size: 9px; opacity: 0.8; }

        .recibo-box {

                           
            border: 2px solid {{ $primary }};
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 72px;
            font-w
e               ight: bold;

                        color: {{ $primary }};
            opacity: 0.06;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }


           
           
               .recibo-content { position: relative; z-index: 1; }

        .recibo-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
        }



           
               .recibo-row:las
           t-child { border-bo
           ttom: none; }

           
       
        .recibo-label {
            font-w
               eight: bold;
             
           color: #374151; f
       ont-size: 10px; }
        .recibo-value { color: {{ $text }}; font-size: 10px; }

        .valor-destaque {
            font-size: 24px;
            font-w
               eight: bold;

                        color: {{ $primary }};
            text-align: center;
            padding: 15px 0;

               
                        border-top: 2px solid {{ $primary }};
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 7px;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .badge-pago {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
        }
    </s
tyle>
</head>
<body>
    <div class="header">
        <div>
        @php
            $logoPath = $config->empresa_logo ?? null;
            if ($logoPath && !file_exists($logoPath))
                $logoPath = storage_path('app/public/' . $logoPath);
        @endphp
        @if($logoPath && file_exists($logoPath))

               <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img">
        @else

            <div style="font-size: 14px; font-weight: bold; color: {{ $primary }};">{{ $config->nome_sistema ?? 'Sistema' }}</div>
        @endif
            <div class="company-info">

                               {{ $config->empresa_cnpj ?? '' }} | {{ $config->empresa_telefone ?? '' }}<br>{{ $config->empresa_email ?? '' }}
            </div>
        </div>
        <div class="header-title">
            <div class="doc-title">Recibo de Pagamento</div>
            <div class="doc-date">Emitido em: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="recibo-box">
        <div class="watermark">PAGO</div>
        <div class="recibo-content">
            <div style="text-align: center; margin-bottom: 15px;">
                <span class="badge-pago">✓ PAGAMENTO CONFIRMADO</span>
            </div>

            <div class="recibo-row">
                <span class="recibo-label">Recebido de:</span>
                <span class="recibo-value">{{ $financeiro->cadastro->nome ?? 'N/A' }}</span>
            </div>

            <div class="recibo-row">
                <span class="recibo-label">Descrição:</span>
                <span class="recibo-value">{{ $financeiro->descricao ?? 'Pagamento de serviço' }}</span>
            </div>

            @if($financeiro->orcamento)
                <div class="recibo-row">
                    <span class="recibo-label">Ref. Orçamento:</span>
                    <span class="recibo-value">#{{ $financeiro->orcamento->numero_orcamento }}</span>
                </div>
            @endif

            @if($financeiro->ordemServico)
                <div class="recibo-row">
                    <span class="recibo-label">Ref. Ordem de Serviço:</span>
                    <span class="recibo-value">OS #{{ $financeiro->ordemServico->numero_os }}</span>
                </div>
            @endif

            <div class="recibo-row">
                <span class="recibo-label">Forma de Pagamento:</span>
                <span class="recibo-value">{{ ucfirst($financeiro->forma_pagamento ?? 'Não informado') }}</span>
            </div>

            <div class="recibo-row">
                <span
                    class="recibo-label">Data do Pagamento:</span>
                <span class="recibo-value">{{ $financeiro->data_pagamento ? $financeiro->data_pagamento->format('d/m/Y H:i') : ($financeiro->data ? $financeiro->data->format('d/m/Y') : 'N/A') }}</span>
            </div>

            <div c
               lass="valor-destaque">
                R$ {{ number_format($financeiro->valor_pago > 0 ? $financeiro->valor_pago : $financeiro->valor, 2, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="footer">
        Este recibo é um comprovante gerencial de pagamento. AUTONOMIA ILIMITADA {{ date('Y') }}<br>
        Documento gerado automaticamente — {{ $config->empresa_nome ?? '' }}
    </d
iv>
</body></html>
