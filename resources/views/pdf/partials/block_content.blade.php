@if($type === 'totais')
    <div class="section-header" style="margin-top: 5px;">{{ $config->pdf_titulo_valores ?? 'VALORES' }}</div>
    <div class="valores-box">
        <div class="valor-row">
            <span>Subtotal:</span>
            <span><strong>R$ {{ number_format($orcamento->valor_total, 2, ',', '.') }}</strong></span>
        </div>

        @php
            // Logic duplicated here or passed from parent? passed from parent is better but we recalculate for safety
            $valorFinal = $orcamento->valor_final_editado ?? $orcamento->valor_total;
            $descontoPrestador = $orcamento->desconto_prestador ?? 0;
            if ($descontoPrestador > 0 && !$orcamento->valor_final_editado) {
                $valorFinal -= $descontoPrestador;
            }
            // Recalculate PIX discount for display
            $percentual = $config->financeiro_desconto_avista ?? 10;
            $descontoPix = 0;
            if ($orcamento->aplicar_desconto_pix && $percentual > 0) {
                $descontoPix = ($valorFinal * $percentual) / 100;
                // If paying with PIX, it subtracts. But here we just show the calculation.
            }
        @endphp

        @if($descontoPrestador > 0)
            <div class="valor-row desconto-prestador">
                <span>Desconto Extra:</span>
                <span>- R$ {{ number_format($descontoPrestador, 2, ',', '.') }}</span>
            </div>
        @endif

        <div class="valor-row-separator"></div>

        <div class="valor-total-box">
            <div class="valor-total-label">VALOR FINAL</div>
            <div class="valor-total-value">R$ {{ number_format($valorFinal, 2, ',', '.') }}</div>
        </div>

        <!-- PARCELAMENTO -->
        @if(isset($config->financeiro_parcelamento) && is_array($config->financeiro_parcelamento) && count($config->financeiro_parcelamento) > 0)
            <div class="parcelamento-box"
                style="margin-top: 15px; font-size: 10px; border-top: 1px dashed #ddd; padding-top: 10px;">
                <div style="font-weight: bold; margin-bottom: 5px;">CONDIÇÕES DE PARCELAMENTO (CARTÃO):</div>
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($config->financeiro_parcelamento as $parcelaConfig)
                        @php
                            $qtd = (int) ($parcelaConfig['parcelas'] ?? 1);
                            $taxa = (float) ($parcelaConfig['taxa'] ?? 0);

                            // Calcula valor com juros
                            $valorComJuros = $valorFinal + ($valorFinal * ($taxa / 100));
                            $valorParcela = $valorComJuros / $qtd;
                        @endphp
                        <tr>
                            <td style="padding: 2px 0;">{{ $qtd }}x de R$ {{ number_format($valorParcela, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endif

@if($type === 'pix')
    @if($orcamento->pdf_incluir_pix && $orcamento->pix_qrcode_base64)
        <div class="pix-box">
            <div class="pix-title">💚 PAGAMENTO VIA PIX</div>
            <div class="pix-qrcode" style="text-align: center; margin: 5px 0;">
                <img src="{{ $orcamento->pix_qrcode_base64 }}" alt="QR Code PIX" style="width: 70px; height: auto;">
            </div>

            @php
                // Recalculate Final Value locally if needed
                $valorFinal = $orcamento->valor_final_editado ?? $orcamento->valor_total;
                if (($orcamento->desconto_prestador ?? 0) > 0 && !$orcamento->valor_final_editado) {
                    $valorFinal -= $orcamento->desconto_prestador;
                }
                $percentual = $config->financeiro_desconto_avista ?? 10;
                if ($orcamento->aplicar_desconto_pix && $percentual > 0) {
                    $valorFinal -= ($valorFinal * $percentual / 100);
                }
             @endphp

            <div class="pix-valor" style="font-size: 1.1em; font-weight: bold;">R$ {{ number_format($valorFinal, 2, ',', '.') }}
            </div>

            <div class="pix-desconto" style="font-size: 8px; margin-top:2px;">
                (Já com {{ $config->financeiro_desconto_avista ?? 10 }}% de desconto)
            </div>

            @if($orcamento->pix_copia_cola)
                <div style="margin-top: 8px; position: relative;">
                    <!-- Label discreto -->
                    <div style="font-size: 7px; color: #777; margin-bottom: 2px;">PIX COPIA E COLA:</div>
                    <div class="pix-code" style="
                                                word-break: break-all; 
                                                font-size: 8px; 
                                                text-align: left; 
                                                color: #333; 
                                                padding: 6px; 
                                                background: #fdfdfd; 
                                                border: 1px dashed #a7f3d0; 
                                                border-radius: 4px;
                                                line-height: 1.25;
                                             ">
                        {{ $orcamento->pix_copia_cola }}
                    </div>
                </div>
            @endif
        </div>
    @endif
@endif

@if($type === 'texto_garantia')
    <div style="border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #fafafa;">
        <strong>Aviso Importante:</strong><br>
        {{ $config->pdf_texto_garantia ?? '' }}
    </div>
@endif