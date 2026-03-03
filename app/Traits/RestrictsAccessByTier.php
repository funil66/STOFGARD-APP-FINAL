<?php

namespace App\Traits;

use Stancl\Tenancy\Resolvers\DomainTenantResolver;

trait RestrictsAccessByTier
{
    /**
     * Verifica se o Tenant logado tem um plano que permite ver este Resource/Página.
     */
    public static function canAccess(): bool
    {
        $tenant = tenancy()->tenant;

        // Se não tiver tenant, permite tudo (pode ser o mestre local)
        if (!$tenant) {
            return true;
        }

        // Recupera o plano da model
        $planoAtual = strtolower($tenant->plan ?? 'free');
        $planosPermitidos = static::getAllowedTiers();

        return in_array($planoAtual, $planosPermitidos);
    }

    // Default: Permite apenas para planos PRO e ELITE.
    // Pode ser sobrescrito dentro do Resource específico, se quiser.
    public static function getAllowedTiers(): array
    {
        return ['pro', 'elite'];
    }
}
