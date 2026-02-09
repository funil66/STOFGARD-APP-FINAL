<?php

namespace App\Services;

use App\Enums\ServiceType;
use Illuminate\Support\Collection;

class ServiceTypeManager
{
    /**
     * Retorna todas as definições de Tipos de Serviço.
     * Mescla os defaults do Enum com as personalizações do banco (Configuracoes).
     */
    public static function getAll(): Collection
    {
        // 1. Carrega personalizações do banco
        $customSettings = settings()->get('system_service_types', []);

        // Se vier como array json decode (caso não tenha sido castado)
        if (is_string($customSettings)) {
            $customSettings = json_decode($customSettings, true) ?? [];
        }

        // 2. Transforma em Collection key-based para facilitar merge
        $customized = collect($customSettings)->keyBy('slug');

        // 3. Itera sobre o Enum padrão para garantir que todos existam
        $defaults = collect(ServiceType::cases())->map(function ($enum) use ($customized) {
            $slug = $enum->value;

            // Pega customização ou usa default do Enum
            $custom = $customized->get($slug);

            return [
                'slug' => $slug,
                'label' => $custom['label'] ?? $enum->getLabel(),
                'color' => $custom['color'] ?? $enum->getColor(),
                'icon' => $custom['icon'] ?? $enum->getIcon(),
                'descricao_pdf' => $custom['descricao_pdf'] ?? null,
            ];
        });

        return $defaults;
    }

    /**
     * Retorna array [slug => label] para usar em Select::options()
     */
    public static function getOptions(): array
    {
        return self::getAll()->pluck('label', 'slug')->toArray();
    }

    /**
     * Retorna array [slug => color] para badges
     */
    public static function getColors(): array
    {
        return self::getAll()->pluck('color', 'slug')->toArray();
    }

    /**
     * Busca Label por Slug
     */
    public static function getLabel(string $slug): string
    {
        $all = self::getAll();
        $item = $all->firstWhere('slug', $slug);

        return $item['label'] ?? $slug;
    }

    /**
     * Busca Color por Slug
     */
    public static function getColor(string $slug): string
    {
        $all = self::getAll();
        $item = $all->firstWhere('slug', $slug);

        return $item['color'] ?? 'gray';
    }

    /**
     * Busca Descrição para PDF por Slug
     */
    public static function getDescricaoPdf(string $slug): ?string
    {
        $all = self::getAll();
        $item = $all->firstWhere('slug', $slug);

        return $item['descricao_pdf'] ?? null;
    }

    /**
     * Retorna um serviço específico por slug
     */
    public static function get(string $slug): ?array
    {
        return self::getAll()->firstWhere('slug', $slug);
    }
}
