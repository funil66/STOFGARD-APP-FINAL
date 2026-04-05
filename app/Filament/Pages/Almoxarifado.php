<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;

class Almoxarifado extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static string $view = 'filament.pages.almoxarifado';

    protected static ?string $navigationLabel = 'Almoxarifado';

    protected static ?string $title = 'Almoxarifado';

    protected static ?int $navigationSort = 5;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('equipamentos')
                ->label('Equipamentos')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->url(url('/admin/almoxarifado/equipamentos')),

            Action::make('estoques')
                ->label('Estoques')
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->url(url('/admin/almoxarifado/estoques')),

            Action::make('novo_item_estoque')
                ->label('Novo Item de Estoque')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(url('/admin/almoxarifado/estoques/create')),

            Action::make('lista_desejos')
                ->label('Lista de Desejos')
                ->icon('heroicon-o-heart')
                ->color('danger')
                ->url(url('/admin/almoxarifado/lista-desejos')),
        ];
    }
}
