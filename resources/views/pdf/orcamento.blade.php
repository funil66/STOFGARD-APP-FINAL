<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #{{ $orcamento->numero }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; padding: 0; margin: 0; font-size: 14px; }
        .container { width: 100%; padding: 40px; box-sizing: border-box; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #eab308; padding-bottom: 20px; margin-bottom: 30px; }
        .logo h1 { margin: 0; color: #eab308; text-transform: uppercase; font-size: 28px; letter-spacing: 2px; }
        .info { text-align: right; font-size: 12px; color: #64748b; }
        
        /* Client Info */
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .box-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: bold; margin-bottom: 8px; }
        .client-name { font-size: 18px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #eab308; color: white; text-align: left; padding: 12px; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px 12px; border-bottom: 1px solid #e2e8f0; }
        .desc { font-size: 12px; color: #64748b; margin-top: 4px; }
        .val { font-family: monospace; font-size: 14px; }
        
        /* Totals */
        .totals { width: 40%; margin-left: auto; }
        .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; }
        .total-final { font-size: 20px; font-weight: bold; color: #0f172a; border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 10px; }
        
        /* Pix Area */
        .pix-area { margin-top: 40px; background: #fffbeb; border: 2px dashed #eab308; border-radius: 12px; padding: 20px; display: flex; align-items: center; }
        .pix-qr { width: 120px; height: 120px; background: white; padding: 5px; margin-right: 20px; }
        .pix-info h3 { margin: 0 0 5px 0; color: #b45309; }
        .copy-paste { background: white; padding: 10px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 10px; word-break: break-all; border-radius: 4px; color: #64748b; }
        
        .footer { text-align: center; margin-top: 50px; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><h1>STOFGARD</h1></div>
            <div class="info">
                Orçamento #{{ $orcamento->numero }}<br>
                Data: {{ $orcamento->created_at->format('d/m/Y') }}<br>
                Validade: {{ \Carbon\Carbon::parse($orcamento->data_validade)->format('d/m/Y') }}
            </div>
        </div>

        <div class="box">
            <div class="box-title">Dados do Cliente</div>
            <div class="client-name">{{ $orcamento->cliente->nome ?? 'Cliente' }}</div>
            <div>{{ $orcamento->cliente->telefone ?? '' }}</div>
            <div>{{ $orcamento->cliente->email ?? '' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="50%">Descrição</th>
                    <th width="10%" style="text-align: center">Qtd</th>
                    <th width="20%" style="text-align: right">Unitário</th>
                    <th width="20%" style="text-align: right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orcamento->itens as $item)
                <tr>
                    <td>
                        <strong>{{ $item->produto->nome ?? 'Serviço' }}</strong>
                        <div class="desc">{{ $item->descricao }}</div>
                    </td>
                    <td style="text-align: center">{{ $item->quantidade }}</td>
                    <td style="text-align: right" class="val">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td style="text-align: right" class="val"><strong>R$ {{ number_format($item->valor_total, 2, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>R$ {{ number_format($orcamento->valor_subtotal, 2, ',', '.') }}</span>
            </div>
            @if($orcamento->valor_desconto > 0)
            <div class="row" style="color: #ef4444">
                <span>Desconto</span>
                <span>- R$ {{ number_format($orcamento->valor_desconto, 2, ',', '.') }}</span>
            </div>
            @endif
            <div class="row total-final">
                <span>TOTAL</span>
                <span>R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</span>
            </div>
        </div>

        @if($orcamento->pix_qrcode_base64 && $orcamento->pix_copia_cola)
        <div class="pix-area">
            <img src="data:image/png;base64, {{ $orcamento->pix_qrcode_base64 }}" class="pix-qr">
            
            <div class="pix-info">
                <h3>Pague com PIX agora</h3>
                <p style="margin: 0 0 10px 0; font-size: 12px;">Use o aplicativo do seu banco para ler o QR Code ou copie o código abaixo.</p>
                <div class="copy-paste">
                    {{ $orcamento->pix_copia_cola }}
                </div>
            </div>
        </div>
        @endif

        <div class="footer">
            Stofgard Impermeabilizações • Todos os direitos reservados
        </div>
    </div>
</body>
</html>