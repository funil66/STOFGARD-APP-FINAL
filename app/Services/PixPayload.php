<?php
namespace App\Services;

class PixPayload {
    public static function gerar(string $chave, string $beneficiario, string $cidade, string $txid, float $valor): string {
        // 1. HIGIENIZAÇÃO DA CHAVE (Inteligente)
        $chave = trim($chave);

        // Se tem @, é E-mail. Mantém.
        if (strpos($chave, '@') !== false) {
            // Email ok
        }
        // Se tem hifens e letras (ex: 123-ab-cd), é Chave Aleatória (EVP).
        // Removemos tudo que não for letra, número ou hífen.
        elseif (preg_match('/[a-zA-Z]/', $chave) && strpos($chave, '-') !== false) {
            $chave = preg_replace('/[^a-zA-Z0-9\-]/', '', $chave);
        }
        // Se só tem números ou caracteres de telefone/CPF
        else {
            $numeros = preg_replace('/[^0-9]/', '', $chave);
            // CPF (11) ou CNPJ (14)
            if (strlen($numeros) === 11 || strlen($numeros) === 14) {
                $chave = $numeros; 
            } 
            // Provável Celular (DDD + 9 + 8 digitos = 11) ou Fixo (10)
            // No PIX, telefone SEMPRE precisa do +55
            else {
                $chave = '+55' . $numeros;
            }
        }

        // 2. FORMATAÇÃO DE CAMPOS
        $beneficiario = substr(self::removeAcentos($beneficiario), 0, 25);
        $cidade = substr(self::removeAcentos($cidade), 0, 15);
        $valorStr = number_format($valor, 2, '.', '');
        
        // TXID deve ser *** se vazio, ou alfanumérico limpo
        $txid = empty($txid) ? '***' : substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 25);

        // 3. MONTAGEM DO PAYLOAD
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

    private static function len($strOrInt) { 
        $len = is_int($strOrInt) ? $strOrInt : strlen($strOrInt);
        return str_pad($len, 2, '0', STR_PAD_LEFT); 
    }

    private static function removeAcentos($string) { return preg_replace(['/(á|à|ã|â|ä)/','/(é|è|ê|ë)/','/(í|ì|î|ï)/','/(ó|ò|õ|ô|ö)/','/(ú|ù|û|ü)/','/(ç)/','/(ñ)/','/(Á|À|Ã|Â|Ä)/','/(É|È|Ê|Ë)/','/(Í|Ì|Î|Ï)/','/(Ó|Ò|Õ|Ô|Ö)/','/(Ú|Ù|Û|Ü)/','/(Ç)/','/(Ñ)/'], ['a','e','i','o','u','c','n','A','E','I','O','U','C','N'], $string); }

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

