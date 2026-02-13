<?php

namespace App\Support\Filament;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Colors\Color;

class StofgardTable
{
    /**
     * Retorna o padrão de ações para todas as tabelas do sistema.
     * Agrupa em um dropdown "Ações" para limpar o visual e corrigir espaçamento.
     */
    public static function defaultActions(
        bool $view = true,
        bool $edit = true,
        bool $delete = true,
        array $extraActions = [],
        bool $grouped = false
    ): array {
        $actions = [];

        // Ação de VISUALIZAR (Ficha)
        if ($view) {
            $actions[] = Tables\Actions\ViewAction::make()
                ->label('Visualizar')
                ->icon('heroicon-m-document-text') // Ícone SVG nativo (M = Medium)
                ->color('info');
        }

        // Ação de EDITAR
        if ($edit) {
            $actions[] = Tables\Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-m-pencil-square')
                ->color('primary');
        }

        // Merge com ações extras que o recurso específico precisar (ex: Baixar PDF)
        $actions = array_merge($actions, $extraActions);

        // Ação de EXCLUIR (Sempre por último para segurança)
        if ($delete) {
            $actions[] = Tables\Actions\DeleteAction::make()
                ->label('Excluir')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation();
        }

        if (!$grouped) {
            return $actions;
        }

        // Retorna tudo agrupado para não estourar a largura da tabela
        return [
            ActionGroup::make($actions)
                ->label('Gerenciar')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->tooltip('Opções do registro'),
        ];
    }
    /**
     * Retorna o padrão de ações em massa (checkbox).
     */
    public static function defaultBulkActions(array $extraBulkActions = []): array
    {
        $actions = array_merge($extraBulkActions, [
            Tables\Actions\DeleteBulkAction::make()
                ->label('Excluir Selecionados')
                ->icon('heroicon-m-trash'),
        ]);

        return [
            Tables\Actions\BulkActionGroup::make($actions)
                ->label('Ações em Massa')
                ->icon('heroicon-m-cog-6-tooth') // Engrenagem
                ->color('primary'),
        ];
    }
}
