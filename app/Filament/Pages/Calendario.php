<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AgendaCalendarWidget;
use Filament\Pages\Page;

class Calendario extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.calendario';

    protected static ?string $title = 'Calendário';

    protected static ?string $navigationLabel = 'Calendário';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 4;

    protected function getHeaderWidgets(): array
    {
        return [
            AgendaCalendarWidget::class,
        ];
    }
}
