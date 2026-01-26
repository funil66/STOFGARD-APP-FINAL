<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WeatherWidget extends Widget
{
    protected static string $view = 'filament.widgets.weather-widget';

    // O SEGREDO ESTÁ AQUI: Força o widget a ocupar todas as colunas disponíveis
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
}
