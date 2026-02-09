<?php

namespace App\Services\Pix;

class PixKeyValidatorService
{
    /**
     * Valida uma chave PIX de acordo com seu tipo
     */
    public static function validate(string $chave, string $tipo, ?string $codigoPais = null): array
    {
        $resultado = [
            'valida' => false,
            'chave_formatada' => $chave,
            'erro' => null,
        ];

        switch (strtolower($tipo)) {
            case 'cpf':
                return self::validarCPF($chave);

            case 'cnpj':
                return self::validarCNPJ($chave);

            case 'telefone':
                return self::validarTelefone($chave, $codigoPais);

            case 'email':
                return self::validarEmail($chave);

            case 'aleatoria':
                return self::validarChaveAleatoria($chave);

            default:
                $resultado['erro'] = 'Tipo de chave não suportado';

                return $resultado;
        }
    }

    /**
     * Valida CPF
     */
    private static function validarCPF(string $cpf): array
    {
        $resultado = ['valida' => false, 'chave_formatada' => $cpf, 'erro' => null];

        // Remove pontos e traços
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpfLimpo) !== 11) {
            $resultado['erro'] = 'CPF deve ter 11 dígitos';

            return $resultado;
        }

        // Verifica se não são todos iguais
        if (str_repeat($cpfLimpo[0], 11) === $cpfLimpo) {
            $resultado['erro'] = 'CPF inválido (todos os dígitos iguais)';

            return $resultado;
        }

        // Validação dos dígitos verificadores
        if (! self::validarDigitosCPF($cpfLimpo)) {
            $resultado['erro'] = 'CPF inválido (dígitos verificadores incorretos)';

            return $resultado;
        }

        $resultado['valida'] = true;
        $resultado['chave_formatada'] = $cpfLimpo;

        return $resultado;
    }

    /**
     * Valida CNPJ
     */
    private static function validarCNPJ(string $cnpj): array
    {
        $resultado = ['valida' => false, 'chave_formatada' => $cnpj, 'erro' => null];

        // Remove pontos, barras e traços
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpjLimpo) !== 14) {
            $resultado['erro'] = 'CNPJ deve ter 14 dígitos';

            return $resultado;
        }

        // Verifica se não são todos iguais
        if (str_repeat($cnpjLimpo[0], 14) === $cnpjLimpo) {
            $resultado['erro'] = 'CNPJ inválido (todos os dígitos iguais)';

            return $resultado;
        }

        // Validação dos dígitos verificadores
        if (! self::validarDigitosCNPJ($cnpjLimpo)) {
            $resultado['erro'] = 'CNPJ inválido (dígitos verificadores incorretos)';

            return $resultado;
        }

        $resultado['valida'] = true;
        $resultado['chave_formatada'] = $cnpjLimpo;

        return $resultado;
    }

    /**
     * Valida Telefone
     */
    private static function validarTelefone(string $telefone, ?string $codigoPais = '55'): array
    {
        $resultado = ['valida' => false, 'chave_formatada' => $telefone, 'erro' => null];

        $telefone = trim($telefone);
        $numeros = preg_replace('/[^0-9]/', '', $telefone);

        // Telefone com código do país completo (+5516999999999)
        if (str_starts_with($telefone, '+55')) {
            if (strlen($numeros) === 13) {
                $ddd = substr($numeros, 2, 2);
                if (self::validarDDD($ddd)) {
                    $resultado['valida'] = true;
                    $resultado['chave_formatada'] = '+'.$numeros;

                    return $resultado;
                }
            }
            $resultado['erro'] = 'Formato de telefone com +55 inválido';

            return $resultado;
        }

        // Telefone apenas com números locais (16999999999 ou 1634567890)
        if (strlen($numeros) === 11 || strlen($numeros) === 10) {
            $ddd = substr($numeros, 0, 2);
            if (self::validarDDD($ddd)) {
                // Se for celular (11 dígitos) deve começar com 9
                if (strlen($numeros) === 11 && substr($numeros, 2, 1) !== '9') {
                    $resultado['erro'] = 'Telefone celular deve começar com 9 após o DDD';

                    return $resultado;
                }
                $resultado['valida'] = true;
                $resultado['chave_formatada'] = '+'.($codigoPais ?? '55').$numeros;

                return $resultado;
            }
            $resultado['erro'] = 'DDD inválido';

            return $resultado;
        }

        $resultado['erro'] = 'Formato de telefone inválido';

        return $resultado;
    }

    /**
     * Valida Email
     */
    private static function validarEmail(string $email): array
    {
        $resultado = ['valida' => false, 'chave_formatada' => $email, 'erro' => null];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resultado['valida'] = true;
            $resultado['chave_formatada'] = strtolower(trim($email));

            return $resultado;
        }

        $resultado['erro'] = 'Formato de e-mail inválido';

        return $resultado;
    }

    /**
     * Valida Chave Aleatória (EVP)
     */
    private static function validarChaveAleatoria(string $chave): array
    {
        $resultado = ['valida' => false, 'chave_formatada' => $chave, 'erro' => null];

        // Formato UUID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave)) {
            $resultado['valida'] = true;
            $resultado['chave_formatada'] = strtolower($chave);

            return $resultado;
        }

        $resultado['erro'] = 'Chave aleatória deve estar no formato UUID';

        return $resultado;
    }

    /**
     * Valida dígitos verificadores do CPF
     */
    private static function validarDigitosCPF(string $cpf): bool
    {
        // Calcula primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if ($digito1 !== intval($cpf[9])) {
            return false;
        }

        // Calcula segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        return $digito2 === intval($cpf[10]);
    }

    /**
     * Valida dígitos verificadores do CNPJ
     */
    private static function validarDigitosCNPJ(string $cnpj): bool
    {
        // Calcula primeiro dígito verificador
        $multiplicador = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += intval($cnpj[$i]) * $multiplicador[$i];
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if ($digito1 !== intval($cnpj[12])) {
            return false;
        }

        // Calcula segundo dígito verificador
        $multiplicador = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += intval($cnpj[$i]) * $multiplicador[$i];
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        return $digito2 === intval($cnpj[13]);
    }

    /**
     * Valida DDD brasileiro
     */
    private static function validarDDD(string $ddd): bool
    {
        $dddsValidos = [
            '11', '12', '13', '14', '15', '16', '17', '18', '19', // SP
            '21', '22', '24', // RJ
            '27', '28', // ES
            '31', '32', '33', '34', '35', '37', '38', // MG
            '41', '42', '43', '44', '45', '46', // PR
            '47', '48', '49', // SC
            '51', '53', '54', '55', // RS
            '61', // DF
            '62', '64', // GO
            '63', // TO
            '65', '66', // MT
            '67', // MS
            '68', // AC
            '69', // RO
            '71', '73', '74', '75', '77', // BA
            '79', // SE
            '81', '87', // PE
            '82', // AL
            '83', // PB
            '84', // RN
            '85', '88', // CE
            '86', '89', // PI
            '91', '93', '94', // PA
            '92', '97', // AM
            '95', // RR
            '96', // AP
            '98', '99', // MA
        ];

        return in_array($ddd, $dddsValidos);
    }
}
