<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Helper global para acessar configurações do sistema.
 * 
 * Uso:
 *   settings('nome_sistema', 'Sistema')
 *   settings('empresa.telefone')
 *   settings()->isAdmin($user)
 *   settings()->get('chave', 'default')
 *   settings()->all()
 */
class SettingsHelper
{
    /**
     * Cache TTL em segundos (1 hora)
     */
    protected int $cacheTtl = 3600;

    /**
     * Prefix para cache
     */
    protected string $cachePrefix = 'settings_';

    /**
     * Obtém uma configuração pelo key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->cachePrefix . $key;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            return Setting::get($key, $default);
        });
    }

    /**
     * Define uma configuração
     */
    public function set(string $key, mixed $value, string $group = 'geral', string $type = 'string'): void
    {
        Setting::set($key, $value, $group, $type);
        Cache::forget($this->cachePrefix . $key);
    }

    /**
     * Obtém todas as configurações como array
     */
    public function all(): array
    {
        return Cache::remember($this->cachePrefix . 'all', $this->cacheTtl, function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Limpa o cache de configurações
     */
    public function clearCache(): void
    {
        $settings = Setting::all();
        foreach ($settings as $setting) {
            Cache::forget($this->cachePrefix . $setting->key);
        }
        Cache::forget($this->cachePrefix . 'all');
    }

    /**
     * Verifica se o usuário é administrador
     * Usa a configuração 'admin_emails' (JSON array) ou verifica is_admin
     */
    public function isAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        // Primeiro verifica flag is_admin
        if ($user->is_admin === true) {
            return true;
        }

        // Depois verifica lista de emails admin
        $adminEmails = $this->get('admin_emails');

        if ($adminEmails) {
            $emails = is_array($adminEmails) ? $adminEmails : json_decode($adminEmails, true);
            if (is_array($emails) && in_array($user->email, $emails)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém dados da empresa formatados
     */
    public function empresa(): array
    {
        return [
            'nome' => $this->get('empresa_nome', 'Minha Empresa'),
            'nome_fantasia' => $this->get('empresa_nome', 'Minha Empresa'),
            'cnpj' => $this->get('empresa_cnpj', ''),
            'telefone' => $this->get('empresa_telefone', ''),
            'email' => $this->get('empresa_email', ''),
            'endereco' => $this->get('empresa_endereco', ''),
            'logo' => $this->get('empresa_logo', ''),
        ];
    }

    /**
     * Obtém configurações do dashboard
     */
    public function dashboard(): array
    {
        return [
            'frase_central' => $this->get('dashboard_frase', 'Bem-vindo ao Sistema'),
            'mostrar_clima' => $this->get('dashboard_mostrar_clima', true),
            'url_clima' => $this->get('url_clima', ''),
        ];
    }

    /**
     * Obtém nome do sistema
     */
    public function nomeSistema(): string
    {
        return $this->get('nome_sistema', 'Sistema');
    }

    /**
     * Obtém URL do logo
     */
    public function logo(): ?string
    {
        $logo = $this->get('empresa_logo');

        if (!$logo) {
            return null;
        }

        // Se já é uma URL completa, retorna
        if (str_starts_with($logo, 'http')) {
            return $logo;
        }

        // Se é um path de storage
        return asset('storage/' . $logo);
    }
}

/**
 * Função helper global
 */
if (!function_exists('settings')) {
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $helper = app(SettingsHelper::class);

        if ($key === null) {
            return $helper;
        }

        return $helper->get($key, $default);
    }
}
