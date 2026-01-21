<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoService
{
    /**
     * Obter valor de uma configuração
     *
     * @param  mixed  $default  Valor padrão se não encontrado
     * @return mixed
     */
    public static function get(string $grupo, string $chave, $default = null)
    {
        if (! Schema::hasTable('configuracoes')) {
            return $default;
        }

        // Cache de 1 hora para performance
        $cacheKey = "config_{$grupo}_{$chave}";

        return Cache::remember($cacheKey, 3600, function () use ($grupo, $chave, $default) {
            $config = Configuracao::where('grupo', $grupo)
                ->where('chave', $chave)
                ->first();

            if (! $config) {
                return $default;
            }

            return match ($config->tipo) {
                'boolean' => filter_var($config->valor, FILTER_VALIDATE_BOOLEAN),
                'number' => (float) $config->valor,
                'json' => json_decode($config->valor, true),
                default => $config->valor,
            };
        });
    }

    /**
     * Definir valor de uma configuração
     *
     * @param  mixed  $valor
     */
    public static function set(
        string $grupo,
        string $chave,
        $valor,
        string $tipo = 'text',
        ?string $descricao = null
    ): Configuracao {
        // Converter array/objeto para JSON
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor);
            $tipo = 'json';
        }

        // Converter boolean para string
        if (is_bool($valor)) {
            $valor = $valor ? '1' : '0';
            $tipo = 'boolean';
        }

        $config = Configuracao::updateOrCreate(
            ['grupo' => $grupo, 'chave' => $chave],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'descricao' => $descricao,
            ]
        );

        // Limpar cache
        Cache::forget("config_{$grupo}_{$chave}");

        return $config;
    }

    /**
     * Obter todas as configurações de um grupo
     */
    public static function getGrupo(string $grupo): array
    {
        $configs = Configuracao::where('grupo', $grupo)->get();

        $resultado = [];
        foreach ($configs as $config) {
            $resultado[$config->chave] = match ($config->tipo) {
                'boolean' => filter_var($config->valor, FILTER_VALIDATE_BOOLEAN),
                'number' => (float) $config->valor,
                'json' => json_decode($config->valor, true),
                default => $config->valor,
            };
        }

        return $resultado;
    }

    /**
     * Verificar se uma configuração existe
     */
    public static function has(string $grupo, string $chave): bool
    {
        if (! Schema::hasTable('configuracoes')) {
            return false;
        }

        return Configuracao::where('grupo', $grupo)
            ->where('chave', $chave)
            ->exists();
    }

    /**
     * Remover uma configuração
     */
    public static function delete(string $grupo, string $chave): bool
    {
        Cache::forget("config_{$grupo}_{$chave}");

        if (! Schema::hasTable('configuracoes')) {
            return false;
        }

        return Configuracao::where('grupo', $grupo)
            ->where('chave', $chave)
            ->delete() > 0;
    }

    /**
     * Limpar todo o cache de configurações
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Atalhos para configurações comuns
     */
    public static function empresa(string $chave, $default = null)
    {
        return self::get('empresa', $chave, $default);
    }

    public static function financeiro(string $chave, $default = null)
    {
        return self::get('financeiro', $chave, $default);
    }

    public static function nfse(string $chave, $default = null)
    {
        return self::get('nfse', $chave, $default);
    }

    public static function sistema(string $chave, $default = null)
    {
        return self::get('sistema', $chave, $default);
    }

    public static function notificacoes(string $chave, $default = null)
    {
        return self::get('notificacoes', $chave, $default);
    }
}
