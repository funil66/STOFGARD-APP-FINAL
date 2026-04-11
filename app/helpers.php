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

if (!function_exists('company_trade_name')) {
    /**
     * Resolve o melhor nome fantasia da empresa entre settings, configuracoes e tenant.
     */
    function company_trade_name(?string $default = null): ?string
    {
        return company_pdf_identity()['empresa_nome'] ?? $default;
    }
}

if (!function_exists('company_pdf_identity')) {
    /**
     * Resolve os dados públicos da empresa para cabeçalhos de PDF sem depender de cache.
     *
     * @return array{empresa_nome:?string,empresa_cnpj:?string,empresa_telefone:?string,empresa_email:?string,empresa_logo:?string}
     */
    function company_pdf_identity(): array
    {
        $freshSettings = [];

        try {
            $freshSettings = \App\Models\Setting::query()
                ->whereIn('key', [
                    'empresa_nome',
                    'nome_sistema',
                    'empresa_cnpj',
                    'empresa_telefone',
                    'empresa_email',
                    'empresa_logo',
                    'email_sistema',
                    'telefone_sistema',
                ])
                ->pluck('value', 'key')
                ->toArray();
        } catch (\Throwable) {
        }

        $tenantConfig = null;
        try {
            $tenantConfig = \App\Models\Configuracao::query()
                ->whereNotNull('empresa_nome')
                ->where('empresa_nome', '!=', '')
                ->latest('id')
                ->first();

            if (!$tenantConfig) {
                $tenantConfig = \App\Models\Configuracao::query()->latest('id')->first();
            }
        } catch (\Throwable) {
        }

        $normalize = static function (?string $value): ?string {
            $value = is_string($value) ? trim($value) : null;
            return blank($value) ? null : $value;
        };

        $isGenericName = static function (?string $value): bool {
            if (blank($value)) {
                return true;
            }

            $normalized = mb_strtolower(trim($value));
            if (in_array($normalized, ['stofgard', 'autonomia ilimitada', 'minha empresa', 'sistema', 'empresa'], true)) {
                return true;
            }

            return str_contains($normalized, 'minhaempresa');
        };

        $isGenericEmail = static function (?string $value): bool {
            if (blank($value)) {
                return true;
            }

            $normalized = mb_strtolower(trim($value));
            return str_contains($normalized, 'minhaempresa')
                || str_contains($normalized, '@example')
                || $normalized === 'email@email.com';
        };

        $isGenericCnpj = static function (?string $value): bool {
            if (blank($value)) {
                return true;
            }

            $normalized = preg_replace('/\D+/', '', trim($value));
            return $normalized === '00000000000100';
        };

        $isGenericPhone = static function (?string $value): bool {
            if (blank($value)) {
                return true;
            }

            $normalized = preg_replace('/\D+/', '', trim($value));
            return in_array($normalized, ['0000000000', '00000000000'], true);
        };

        $nameCandidates = [
            $normalize($freshSettings['empresa_nome'] ?? null),
            $normalize($freshSettings['nome_sistema'] ?? null),
            $normalize($tenantConfig?->empresa_nome ?? null),
            $normalize(function_exists('tenant') && tenant() ? (tenant()->name ?? null) : null),
        ];

        $tradeName = null;
        foreach ($nameCandidates as $candidate) {
            if (blank($candidate) || $isGenericName($candidate)) {
                continue;
            }

            if ($tradeName === null || mb_strlen($candidate) > mb_strlen($tradeName)) {
                $tradeName = $candidate;
            }
        }

        $companyEmail = null;
        foreach ([$freshSettings['empresa_email'] ?? null, $freshSettings['email_sistema'] ?? null, $tenantConfig?->empresa_email ?? null] as $candidate) {
            $candidate = $normalize($candidate);
            if (blank($candidate) || $isGenericEmail($candidate)) {
                continue;
            }
            $companyEmail = $candidate;
            break;
        }

        $companyCnpj = null;
        foreach ([$freshSettings['empresa_cnpj'] ?? null, $tenantConfig?->empresa_cnpj ?? null] as $candidate) {
            $candidate = $normalize($candidate);
            if (blank($candidate) || $isGenericCnpj($candidate)) {
                continue;
            }
            $companyCnpj = $candidate;
            break;
        }

        $companyPhone = null;
        foreach ([$freshSettings['empresa_telefone'] ?? null, $freshSettings['telefone_sistema'] ?? null, $tenantConfig?->empresa_telefone ?? null] as $candidate) {
            $candidate = $normalize($candidate);
            if (blank($candidate) || $isGenericPhone($candidate)) {
                continue;
            }
            $companyPhone = $candidate;
            break;
        }

        $companyLogo = null;
        foreach ([$freshSettings['empresa_logo'] ?? null, $tenantConfig?->empresa_logo ?? null] as $candidate) {
            $candidate = $normalize($candidate);
            if (blank($candidate)) {
                continue;
            }
            $companyLogo = $candidate;
            break;
        }

        return [
            'empresa_nome' => $tradeName,
            'empresa_cnpj' => $companyCnpj,
            'empresa_telefone' => $companyPhone,
            'empresa_email' => $companyEmail,
            'empresa_logo' => $companyLogo,
        ];
    }
}

