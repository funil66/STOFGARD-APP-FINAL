<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WeatherWidget extends Widget
{
    protected static string $view = 'filament.widgets.weather-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1; // Prioridade Topo
}
