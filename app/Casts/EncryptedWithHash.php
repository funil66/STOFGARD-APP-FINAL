<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Cast: Campo criptografado com hash HMAC para busca exata.
 *
 * Funciona em par com as colunas *_hash na tabela.
 * Ao salvar, a Model deve também salvar o HMAC no campo correspondente.
 *
 * USO no Model:
 *   protected $casts = [
 *     'documento' => EncryptedWithHash::class,
 *   ];
 *
 * BUSCA EXATA (ex: buscar por CPF):
 *   Cadastro::where('documento_hash', EncryptedWithHash::makeHash($cpf))->first()
 *
 * FUNDAMENTO:
 * HMAC-SHA256 com APP_KEY como segredo. Significa que mesmo com acesso ao banco,
 * não é possível reverter o hash sem a key da aplicação.
 * Compatível com rotação de APP_KEY via APP_PREVIOUS_KEYS do Laravel 10.
 */
class EncryptedWithHash implements CastsAttributes
{
    /**
     * Decriptografa ao ler do banco.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            Log::warning("[EncryptedWithHash] Falha ao descriptografar campo {$key}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Criptografa ao salvar e atualiza o campo _hash correspondente.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (is_null($value)) {
            return [
                $key => null,
                "{$key}_hash" => null,
            ];
        }

        $value = (string) $value;

        return [
            $key => Crypt::encryptString($value),
            "{$key}_hash" => self::makeHash($value),
        ];
    }

    /**
     * Gera o HMAC-SHA256 do valor para busca exata.
     * Usa a APP_KEY como chave secreta do HMAC.
     *
     * @param  string  $value  Valor plaintext (ex: CPF sem máscara)
     */
    public static function makeHash(string $value): string
    {
        // Normaliza para remover formatação antes de hashear
        $normalized = preg_replace('/[^a-zA-Z0-9@._\-]/', '', mb_strtolower(trim($value)));
        $secret = config('app.key');

        return hash_hmac('sha256', $normalized, $secret);
    }
}
