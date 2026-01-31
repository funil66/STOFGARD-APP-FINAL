<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;

class WeatherWidget extends Widget
{
    protected static string $view = 'filament.widgets.weather-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public $city = 'Ribeirão Preto';
    public $temp;
    public $condition;
    public $icon;
    public $humidity;
    public $wind;

    public $greeting;
    public $userName;

    public function mount()
    {
        // Saudação personalizada
        $hour = date('H');
        if ($hour >= 5 && $hour < 12) {
            $this->greeting = 'Bom dia';
        } elseif ($hour >= 12 && $hour < 18) {
            $this->greeting = 'Boa tarde';
        } else {
            $this->greeting = 'Boa noite';
        }

        $this->userName = auth()->user()->name ?? 'Colaborador';

        // Coordenadas de Ribeirão Preto
        $lat = -21.1704;
        $lon = -47.8103;

        try {
            $response = Http::get("https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=America%2FSao_Paulo");

            if ($response->successful()) {
                $data = $response->json();
                $current = $data['current'];

                $this->temp = round($current['temperature_2m']);
                $this->humidity = $current['relative_humidity_2m'];
                $this->wind = round($current['wind_speed_10m']);

                $code = $current['weather_code'];
                $this->setWeatherIcon($code);
            }
        } catch (\Exception $e) {
            $this->temp = '--';
            $this->condition = 'Indisponível';
        }
    }

    protected function setWeatherIcon($code)
    {
        // WMO Weather interpretation codes (WW)
        // https://open-meteo.com/en/docs
        if ($code === 0) {
            $this->condition = 'Céu Limpo';
            $this->icon = '☀️';
        } elseif (in_array($code, [1, 2, 3])) {
            $this->condition = 'Parcialmente Nublado';
            $this->icon = '⛅';
        } elseif (in_array($code, [45, 48])) {
            $this->condition = 'Nevoeiro';
            $this->icon = '🌫️';
        } elseif (in_array($code, [51, 53, 55, 61, 63, 65])) {
            $this->condition = 'Chuva';
            $this->icon = '🌧️';
        } elseif (in_array($code, [71, 73, 75, 77])) {
            $this->condition = 'Neve'; // Raro em RP, mas ok
            $this->icon = '❄️';
        } elseif (in_array($code, [80, 81, 82])) {
            $this->condition = 'Pancadas de Chuva';
            $this->icon = '🌦️';
        } elseif (in_array($code, [95, 96, 99])) {
            $this->condition = 'Tempestade';
            $this->icon = '⛈️';
        } else {
            $this->condition = 'Nublado';
            $this->icon = '☁️';
        }
    }
}
