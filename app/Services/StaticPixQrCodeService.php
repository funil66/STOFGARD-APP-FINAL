<?php

namespace App\Services;

use App\Models\Orcamento;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StaticPixQrCodeService
{
    /**
     * Gera e salva o QR Code PIX para um orçamento.
     *
     * @return bool Retorna true em sucesso, false em falha.
     */
    public function generate(Orcamento $orcamento): bool
    {
        if ($orcamento->forma_pagamento !== 'pix' || ! $orcamento->pix_chave_tipo || $orcamento->valor_total <= 0) {
            if ($orcamento->pix_qrcode_base64 !== null) {
                $orcamento->updateQuietly([
                    'pix_qrcode_base64' => null,
                    'pix_copia_cola' => null,
                ]);
            }

            return true;
        }

        try {
            $chavePix = $orcamento->pix_chave_tipo === 'cnpj'
                ? '58794846000120'
                : '+5516997539698';

            $pixPayload = $this->gerarPayloadPix(
                $chavePix,
                $orcamento->cliente->nome ?? 'Cliente',
                $orcamento->valor_total,
                $orcamento->numero_orcamento
            );

            // Gera o QR Code preferencialmente em PNG e, em caso de falha (ex.: GD/Imagick não disponível), tenta SVG como fallback.
            $qrDataUri = null;

            try {
                $qrCodeImage = QrCode::format('png')->size(300)->generate($pixPayload);
                $qrDataUri = 'data:image/png;base64,'.base64_encode($qrCodeImage);
            } catch (\Exception $e) {
                // tenta SVG como fallback
                try {
                    $qrSvg = QrCode::format('svg')->size(300)->generate($pixPayload);
                    $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
                } catch (\Exception $e2) {
                    Log::error('Erro ao gerar QR Code PIX (PNG e SVG falharam) para o orçamento '.$orcamento->id.': '.$e2->getMessage());
                    return false;
                }
            }

            $orcamento->updateQuietly([
                'pix_qrcode_base64' => $qrDataUri,
                'pix_copia_cola' => $pixPayload,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar QR Code PIX para o orçamento '.$orcamento->id.': '.$e->getMessage());

            return false;
        }
    }

    /**
     * Gera o payload PIX no formato EMV
     */
    private function gerarPayloadPix(string $chavePix, string $beneficiario, float $valor, string $identificador): string
    {
        $payload = '';
        $payload .= $this->gerarCampoEMV('00', '01');
        $gui = $this->gerarCampoEMV('00', 'BR.GOV.BCB.PIX');
        $key = $this->gerarCampoEMV('01', $chavePix);
        $payload .= $this->gerarCampoEMV('26', $gui.$key);
        $payload .= $this->gerarCampoEMV('52', '0000');
        $payload .= $this->gerarCampoEMV('53', '986');
        $payload .= $this->gerarCampoEMV('54', number_format($valor, 2, '.', ''));
        $payload .= $this->gerarCampoEMV('58', 'BR');
        $payload .= $this->gerarCampoEMV('59', 'STOFGARD');
        $payload .= $this->gerarCampoEMV('60', 'SAO CARLOS');
        $txid = $this->gerarCampoEMV('05', substr($identificador, 0, 25));
        $payload .= $this->gerarCampoEMV('62', $txid);
        $payload .= '6304';

        $crc16 = $this->calcularCRC16($payload);

        return $payload.$crc16;
    }

    private function gerarCampoEMV(string $id, string $valor): string
    {
        $tamanho = str_pad(strlen($valor), 2, '0', STR_PAD_LEFT);

        return $id.$tamanho.$valor;
    }

    private function calcularCRC16(string $payload): string
    {
        $polynomial = 0x1021;
        $crc = 0xFFFF;
        if (strlen($payload) > 0) {
            for ($i = 0; $i < strlen($payload); $i++) {
                $crc ^= (ord($payload[$i]) << 8);
                for ($j = 0; $j < 8; $j++) {
                    if (($crc & 0x8000) !== 0) {
                        $crc = ($crc << 1) ^ $polynomial;
                    } else {
                        $crc = $crc << 1;
                    }
                }
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
