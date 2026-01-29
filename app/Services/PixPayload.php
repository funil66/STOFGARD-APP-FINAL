<?php

namespace App\Services;

class PixPayload
{
    /**
     * Gera o Payload Completo do PIX (EMV QRCPS-MPM)
     */
    public static function gerar(string $chave, string $beneficiario, string $cidade, string $txtId, float $valor): string
    {
        $chave = self::limparChave($chave);
        $beneficiario = self::tratarTexto($beneficiario, 25);
        $cidade = self::tratarTexto($cidade, 15);
        $txtId = preg_replace('/[^a-zA-Z0-9]/', '', $txtId);
        if (empty($txtId)) $txtId = '***';
        $txtId = substr($txtId, 0, 25);
        $valorStr = number_format((float)$valor, 2, '.', '');

        $payload = "000201";
        $payload .= "010211";
        
        // Merchant Account Information (26)
        $merchantAccount = "0014br.gov.bcb.pix";
        $merchantAccount .= "01" . sprintf("%02d", strlen($chave)) . $chave;
        $payload .= "26" . sprintf("%02d", strlen($merchantAccount)) . $merchantAccount;

        $payload .= "52040000"; // MCC
        $payload .= "5303986";  // Moeda

        if ($valor > 0) {
            $payload .= "54" . sprintf("%02d", strlen($valorStr)) . $valorStr;
        }

        $payload .= "5802BR";
        $payload .= "59" . sprintf("%02d", strlen($beneficiario)) . $beneficiario;
        $payload .= "60" . sprintf("%02d", strlen($cidade)) . $cidade;

        $txField = "05" . sprintf("%02d", strlen($txtId)) . $txtId;
        $payload .= "62" . sprintf("%02d", strlen($txField)) . $txField;

        $payload .= "6304";
        $payload .= self::calcularCRC16($payload);

        return $payload;
    }

    /**
     * Lê um Payload PIX e extrai os dados reais contidos nele.
     * Simula o escaneamento do QR Code.
     */
    public static function lerPayload(string $payload): array
    {
        $dados = [
            'chave' => null,
            'beneficiario' => null,
            'cidade' => null,
            'valor' => null,
            'txId' => null,
            'valido' => false
        ];

        try {
            // Valida CRC16
            $crcRecebido = substr($payload, -4);
            $payloadSemCrc = substr($payload, 0, -4);
            $crcCalculado = self::calcularCRC16($payloadSemCrc);

            if ($crcRecebido !== $crcCalculado) {
                return $dados; // CRC Inválido
            }

            $dados['valido'] = true;
            $i = 0;
            $len = strlen($payloadSemCrc);

            while ($i < $len) {
                $id = substr($payload, $i, 2);
                $size = (int)substr($payload, $i + 2, 2);
                $content = substr($payload, $i + 4, $size);
                
                switch ($id) {
                    case '26': // Merchant Account Info (Chave)
                        // Dentro do 26, a chave está no ID 01
                        $subI = 0;
                        while($subI < strlen($content)) {
                            $sId = substr($content, $subI, 2);
                            $sSize = (int)substr($content, $subI + 2, 2);
                            $sContent = substr($content, $subI + 4, $sSize);
                            if ($sId === '01') $dados['chave'] = $sContent;
                            $subI += 4 + $sSize;
                        }
                        break;
                    case '54': // Valor
                        $dados['valor'] = $content;
                        break;
                    case '59': // Beneficiário
                        $dados['beneficiario'] = $content;
                        break;
                    case '60': // Cidade
                        $dados['cidade'] = $content;
                        break;
                    case '62': // Additional Data (TxID)
                        $subI = 0;
                        while($subI < strlen($content)) {
                            $sId = substr($content, $subI, 2);
                            $sSize = (int)substr($content, $subI + 2, 2);
                            $sContent = substr($content, $subI + 4, $sSize);
                            if ($sId === '05') $dados['txId'] = $sContent;
                            $subI += 4 + $sSize;
                        }
                        break;
                }
                
                $i += 4 + $size;
            }
        } catch (\Exception $e) {
            // Erro no parse
        }

        return $dados;
    }

    private static function calcularCRC16($payload)
    {
        $polinomio = 0x1021;
        $resultado = 0xFFFF;
        if (($length = strlen($payload)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $resultado ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }
        return strtoupper(sprintf("%04X", $resultado));
    }

    private static function tratarTexto($texto, $limit)
    {
        // Remove acentos brutamente para evitar erro de encoding no QR Code
        $texto = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = preg_replace('/[^a-zA-Z0-9 ]/', '', $texto);
        return strtoupper(substr(trim($texto), 0, $limit));
    }

    private static function limparChave($chave)
    {
        return trim($chave);
    }
}
