<?php

use App\Helpers\SettingsHelper;

if (!function_exists('admin_resource_route')) {
    /**
     * Try to resolve a named route, fallback to a given URL if not found.
     *
     * @param string $name
     * @param string $fallback
     * @param array $params
     * @return string
     */
    function admin_resource_route($name, $fallback = '', $params = [])
    {
        try {
            if (\Route::has($name)) {
                return route($name, $params);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // fallback URL (with replacements if needed)
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $fallback = str_replace('{' . $key . '}', $value, $fallback);
            }
        }
        return url($fallback);
    }
}

if (!function_exists('settings')) {
    /**
     * Acessa configurações do sistema.
     * 
     * Uso:
     *   settings('nome_sistema', 'Sistema')  // Obtém valor com default
     *   settings()->isAdmin($user)           // Verifica se é admin
     *   settings()->empresa()                // Dados da empresa
     *   settings()->dashboard()              // Config do dashboard
     *   settings()->logo()                   // URL do logo
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed|SettingsHelper
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $helper = app(SettingsHelper::class);

        if ($key === null) {
            return $helper;
        }

        return $helper->get($key, $default);
    }
}

