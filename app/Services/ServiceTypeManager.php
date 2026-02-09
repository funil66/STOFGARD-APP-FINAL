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
        // 1. Carrega tipos do banco (Categoria where tipo = 'servico_tipo')
        $dbTypes = \App\Models\Categoria::where('tipo', 'servico_tipo')
            ->where('ativo', true)
            ->get()
            ->keyBy('slug');

        // 2. Carrega Enum padrão
        $enumTypes = collect(ServiceType::cases())->keyBy(fn($e) => $e->value);

        // 3. Mescla: Banco tem prioridade sobre Enum e adiciona novos
        $allSlugs = $dbTypes->keys()->merge($enumTypes->keys())->unique();

        return $allSlugs->map(function ($slug) use ($dbTypes, $enumTypes) {
            $dbItem = $dbTypes->get($slug);
            $enumItem = $enumTypes->get($slug);

            // Prioridade: DB > Enum
            return [
                'slug' => $slug,
                'label' => $dbItem->nome ?? $enumItem?->getLabel() ?? ucfirst($slug),
                'color' => $dbItem->cor ?? $enumItem?->getColor() ?? 'gray',
                'icon' => $dbItem->icone ?? $enumItem?->getIcon() ?? 'heroicon-o-sparkles',
                'descricao_pdf' => $dbItem->descricao ?? $enumItem?->getDescricaoPdf() ?? null,
            ];
        });
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
