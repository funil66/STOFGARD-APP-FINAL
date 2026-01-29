<?php

namespace App\Services\Pix;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class PixMasterService
{
    /**
     * Gera o Payload e a Imagem do QR Code PIX
     * @param string $chave Chave PIX bruta (Email, CPF, Telefone, Aleatória)
     * @param string $beneficiario Nome do titular da conta (Max 25 chars)
     * @param string $cidade Cidade do titular (Sem acentos)
     * @param string $identificador Código de referência (TxID) - Opcional
     * @param float $valor Valor da transação (0.00 para valor livre)
     * @return array ['payload_pix' => string, 'qr_code_img' => string|null, 'chave_real' => string]
     */
    public function gerarQrCode(string $chave, string $beneficiario, string $cidade, string $identificador, float $valor): array
    {
        // 1. Sanitização e Validação da Chave
        $chaveTratada = $this->tratarChave($chave);
        
        // 2. Tratamento de Strings (Padrão EMV)
        $beneficiario = $this->limparTexto($beneficiario, 25);
        $cidade = $this->limparTexto($cidade, 15);
        $identificador = $this->limparTxId($identificador);
        $valorFormatado = number_format($valor, 2, '.', '');

        // 3. Montagem do Payload (Padrão Oficial 010211 - Estático)
        $payload = "000201";
        $payload .= "010211"; // 11 = Estático
        
        // Merchant Account (Chave)
        $gui = "0014br.gov.bcb.pix";
        $merchantInfo = $gui . "01" . sprintf("%02d", strlen($chaveTratada)) . $chaveTratada;
        $payload .= "26" . sprintf("%02d", strlen($merchantInfo)) . $merchantInfo;

        $payload .= "52040000"; // MCC Geral
        $payload .= "5303986";  // Moeda BRL

        // Valor (Obrigatório se > 0)
        if ($valor > 0) {
            $payload .= "54" . sprintf("%02d", strlen($valorFormatado)) . $valorFormatado;
        }

        $payload .= "5802BR"; // País
        $payload .= "59" . sprintf("%02d", strlen($beneficiario)) . $beneficiario;
        $payload .= "60" . sprintf("%02d", strlen($cidade)) . $cidade;

        // Identificador (TxID)
        $txField = "05" . sprintf("%02d", strlen($identificador)) . $identificador;
        $payload .= "62" . sprintf("%02d", strlen($txField)) . $txField;

        // CRC16
        $payload .= "6304";
        $payload .= $this->calcularCRC16($payload);

        // 4. Geração da Imagem
        $base64 = null;
        try {
            $pngData = QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($payload);
            $base64 = 'data:image/png;base64,' . base64_encode($pngData);
        } catch (\Exception $e) {
            Log::error("Erro ao gerar imagem PIX: " . $e->getMessage());
        }

        $result = [
            'payload_pix' => $payload,
            'qr_code_img' => $base64,
            'chave_real' => $chaveTratada,
            'beneficiario_real' => $beneficiario
        ];

        return $result;
    }

    /**
     * Compatibilidade: gerarPix() conforme especificação antiga -> mapeia chaves
     */
    public function gerarPix(string $chave, string $beneficiario, string $cidade, string $identificador, float $valor): array
    {
        $res = $this->gerarQrCode($chave, $beneficiario, $cidade, $identificador, $valor);

        return [
            'payload' => $res['payload_pix'],
            'imagem' => $res['qr_code_img'],
            'chave_real' => $res['chave_real'],
            'beneficiario_real' => $res['beneficiario_real'] ?? $beneficiario,
        ];
    }

    private function tratarChave($chave)
    {
        $chave = trim($chave);
        
        // Se for Email (tem @), retorna igual
        if (strpos($chave, '@') !== false) {
            return $chave;
        }

        // Se for Aleatória (EVP) - Formato UUID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave)) {
            return $chave;
        }

        // Se sobrou, assumimos que é numérico (CPF, CNPJ ou Telefone)
        $numeros = preg_replace('/[^0-9]/', '', $chave);

        // Se for Celular (11 dígitos) ou Fixo (10 dígitos)
        if (strlen($numeros) === 11 || strlen($numeros) === 10) {
            // Verifica se já tem +55
            if (!str_starts_with($chave, '+55')) {
                return '+55' . $numeros;
            }
        }

        // CPF/CNPJ ou Telefone que já tinha +55
        return $chave;
    }

    private function limparTexto($texto, $limite)
    {
        // Remove acentos e caracteres especiais, converte para MAIUSCULAS
        $texto = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = preg_replace('/[^a-zA-Z0-9 ]/', '', $texto);
        return strtoupper(substr(trim($texto), 0, $limite));
    }

    private function limparTxId($txid)
    {
        $txid = preg_replace('/[^a-zA-Z0-9]/', '', $txid);
        if (empty($txid)) return "***";
        return substr($txid, 0, 25);
    }

    private function calcularCRC16($payload)
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
}
