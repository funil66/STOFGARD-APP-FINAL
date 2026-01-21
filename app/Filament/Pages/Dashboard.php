<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = '';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.dashboard';

    public function getHeading(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return 'Dashboard - Stofgard Manager';
    }

    /**
     * Widgets exibidos no dashboard
     */
    public function getWidgets(): array
    {
        return [];
    }

    /**
     * Largura das colunas dos widgets
     */
    public function getColumns(): int|array
    {
        return 1;
    }
}
