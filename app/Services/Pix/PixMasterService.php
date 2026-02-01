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
        // 1. Sanitização e Validação da Chave usando novo validador
        $chaveTratada = $this->tratarChaveComValidacao($chave);
        
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

    /**
     * Método melhorado de tratamento de chave usando validação
     */
    private function tratarChaveComValidacao($chave)
    {
        $chave = trim($chave);
        
        // Detecta o tipo da chave automaticamente
        $tipo = $this->detectarTipoChave($chave);
        
        // Usa o validador para formatar corretamente
        $validacao = PixKeyValidatorService::validate($chave, $tipo);
        
        if ($validacao['valida']) {
            return $validacao['chave_formatada'];
        }
        
        // Fallback para método antigo se validação falhar
        return $this->tratarChave($chave);
    }

    /**
     * Detecta automaticamente o tipo da chave PIX
     */
    private function detectarTipoChave($chave): string
    {
        $chave = trim($chave);
        
        // Email (tem @)
        if (strpos($chave, '@') !== false) {
            return 'email';
        }

        // Chave Aleatória (EVP) - Formato UUID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave)) {
            return 'aleatoria';
        }

        // Remove caracteres não numéricos
        $numeros = preg_replace('/[^0-9]/', '', $chave);

        // CNPJ (14 dígitos)
        if (strlen($numeros) === 14) {
            return 'cnpj';
        }

        // Telefone ou CPF (11 dígitos)
        if (strlen($numeros) === 11) {
            // Verifica se pode ser telefone (DDD + 9 na terceira posição)
            $ddd = substr($numeros, 0, 2);
            $terceiro = substr($numeros, 2, 1);
            
            $dddsValidos = ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28', '31', '32', '33', '34', '35', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48', '49', '51', '53', '54', '55', '61', '62', '63', '64', '65', '66', '67', '68', '69', '71', '73', '74', '75', '77', '79', '81', '82', '83', '84', '85', '86', '87', '88', '89', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
            
            if (in_array($ddd, $dddsValidos) && $terceiro === '9') {
                return 'telefone';
            }
            
            return 'cpf';
        }

        // Telefone fixo (10 dígitos)
        if (strlen($numeros) === 10) {
            return 'telefone';
        }

        // Telefone com código do país (13 dígitos)
        if (strlen($numeros) === 13 && str_starts_with($numeros, '55')) {
            return 'telefone';
        }

        // Default para telefone se começa com +55
        if (str_starts_with($chave, '+55')) {
            return 'telefone';
        }

        return 'cpf'; // Fallback
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

        // Remove todos os caracteres não numéricos para análise
        $numeros = preg_replace('/[^0-9]/', '', $chave);

        // Se for telefone brasileiro com código do país (+5516999999999)
        if (str_starts_with($chave, '+55')) {
            return $chave;
        }

        // Se for telefone brasileiro sem +55 mas com código 55 e DDD válido (5516999999999)
        if (strlen($numeros) === 13 && str_starts_with($numeros, '55')) {
            $ddd = substr($numeros, 2, 2);
            if (in_array($ddd, ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28', '31', '32', '33', '34', '35', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48', '49', '51', '53', '54', '55', '61', '62', '63', '64', '65', '66', '67', '68', '69', '71', '73', '74', '75', '77', '79', '81', '82', '83', '84', '85', '86', '87', '88', '89', '91', '92', '93', '94', '95', '96', '97', '98', '99'])) {
                return '+' . $numeros;
            }
        }

        // Se for telefone brasileiro sem +55 e DDD válido (10 ou 11 dígitos)
        if (strlen($numeros) === 10 || strlen($numeros) === 11) {
            $ddd = substr($numeros, 0, 2);
            if (in_array($ddd, ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28', '31', '32', '33', '34', '35', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48', '49', '51', '53', '54', '55', '61', '62', '63', '64', '65', '66', '67', '68', '69', '71', '73', '74', '75', '77', '79', '81', '82', '83', '84', '85', '86', '87', '88', '89', '91', '92', '93', '94', '95', '96', '97', '98', '99'])) {
                // Se for celular (11 dígitos) deve começar com 9 na terceira posição
                if (strlen($numeros) === 11 && substr($numeros, 2, 1) === '9') {
                    return '+55' . $numeros;
                }
                // Se for fixo (10 dígitos)
                if (strlen($numeros) === 10) {
                    return '+55' . $numeros;
                }
            }
        }

        // Se for CPF (11 dígitos que não foi identificado como telefone)
        if (strlen($numeros) === 11) {
            return $numeros;
        }

        // Se for CNPJ (14 dígitos)
        if (strlen($numeros) === 14) {
            return $numeros;
        }

        // Fallback: retorna a chave original se não conseguir categorizar
        return $chave;
    }

    private function limparTexto($texto, $limite)
    {
        // Remove acentos e caracteres especiais, converte para MAIUSCULAS
        $textoConvertido = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        
        if ($textoConvertido !== false) {
            $texto = $textoConvertido;
        }
        
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
