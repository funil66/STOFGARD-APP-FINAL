<?php
namespace App\Services;

class PixPayload
{
    public static function gerar(string $chave, string $beneficiario, string $cidade, string $txid, float $valor): string
    {
        $chave = trim($chave);
        
        // 1. DETECÇÃO INTELIGENTE DE TIPO DE CHAVE
        
        // E-mail: Contém @
        if (strpos($chave, '@') !== false) {
            // Mantém como está
        }
        // Chave Aleatória (EVP): Contém hífens e tem 36 caracteres
        elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave)) {
            // Mantém os hífens, pois são obrigatórios no EVP
        }
        // Telefone/CPF/CNPJ: Apenas números
        else {
            // Remove tudo que não for número
            $apenasNumeros = preg_replace('/[^0-9]/', '', $chave);
            
            // Se for Telefone (começa com DDD e tem 10 ou 11 digitos), adiciona +55
            // CPF (11) e CNPJ (14) não ganham +55
            if (strlen($apenasNumeros) <= 11 && !in_array(strlen($apenasNumeros), [11])) {
                 // Assumindo telefone se não for CPF exato (CPF tem 11, mas validação de digito é complexa, aqui simplificamos)
                 // Melhor abordagem: Se tem cara de telefone e não é CPF
                 $chave = '+55' . $apenasNumeros;
            } elseif (strlen($apenasNumeros) === 11) {
                // Pode ser CPF ou Celular. No PIX, CPF é só números. Celular precisa de +55.
                // Como desambiguar? Geralmente CPF começa com 0..9. Celular tem DDD.
                // REGRA PRÁTICA: Se o usuário digitou formatado (xx) x..., é telefone.
                if (strpos($chave, '(') !== false || strpos($chave, ' ') !== false) {
                     $chave = '+55' . $apenasNumeros;
                } else {
                     $chave = $apenasNumeros; // Assume CPF
                }
            } else {
                $chave = $apenasNumeros; // CNPJ ou outros
            }
        }

        // 2. Formata Strings
        $beneficiario = substr(self::removeAcentos($beneficiario), 0, 25);
        $cidade = substr(self::removeAcentos($cidade), 0, 15);
        $valorStr = number_format($valor, 2, '.', '');
        $txid = empty($txid) ? '***' : substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 25);

        // 3. Payload BR Code
        $payload = "000201";
        $payload .= "26" . self::len(14 + strlen($chave)) . "0014br.gov.bcb.pix01" . self::len($chave) . $chave;
        $payload .= "52040000";
        $payload .= "5303986";
        if ($valor > 0) $payload .= "54" . self::len($valorStr) . $valorStr;
        $payload .= "5802BR";
        $payload .= "59" . self::len($beneficiario) . $beneficiario;
        $payload .= "60" . self::len($cidade) . $cidade;
        $payload .= "62" . self::len(4 + strlen($txid)) . "05" . self::len($txid) . $txid;
        $payload .= "6304";

        return $payload . self::calculaCRC16($payload);
    }

    private static function len($str) {
        $len = is_int($str) ? $str : strlen($str);
        return str_pad($len, 2, '0', STR_PAD_LEFT);
    }
    
    private static function removeAcentos($string) {
        return preg_replace(['/(á|à|ã|â|ä)/','/(é|è|ê|ë)/','/(í|ì|î|ï)/','/(ó|ò|õ|ô|ö)/','/(ú|ù|û|ü)/','/(ç)/','/(ñ)/','/(Á|À|Ã|Â|Ä)/','/(É|È|Ê|Ë)/','/(Í|Ì|Î|Ï)/','/(Ó|Ò|Õ|Ô|Ö)/','/(Ú|Ù|Û|Ü)/','/(Ç)/','/(Ñ)/'], ['a','e','i','o','u','c','n','A','E','I','O','U','C','N'], $string);
    }

    private static function calculaCRC16($payload) {
        $resultado = 0xFFFF;
        for ($i = 0; $i < strlen($payload); $i++) {
            $resultado ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($resultado <<= 1) & 0x10000) $resultado ^= 0x1021;
                $resultado &= 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
    }
}

