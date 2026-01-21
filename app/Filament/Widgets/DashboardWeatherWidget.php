<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;

class DashboardWeatherWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-weather-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /**
     * Obtém dados do clima de Ribeirão Preto via Open-Meteo API
     */
    public function getWeatherData(): array
    {
        try {
            // Coordenadas de Ribeirão Preto - SP
            $latitude = -21.1767;
            $longitude = -47.8208;

            // Use Open-Meteo's current_weather endpoint
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current_weather' => true,
                'timezone' => 'America/Sao_Paulo',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $current = $data['current_weather'] ?? null;
                $code = $current['weathercode'] ?? 0;
                $badge = $this->getWeatherBadge($code);

                return [
                    'temperature' => round($current['temperature'] ?? 0),
                    'humidity' => $current['relative_humidity'] ?? ($data['hourly']['relativehumidity_2m'][0] ?? '--'),
                    'weather_code' => $code,
                    'description' => $this->getWeatherDescription($code),
                    'icon' => $this->getWeatherIcon($code),
                    'emoji' => $badge['emoji'],
                    'badge_background' => $badge['background'],
                    'city' => 'Ribeirão Preto',
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao obter dados do clima: '.$e->getMessage());
        }

        return [
            'temperature' => '--',
            'humidity' => '--',
            'description' => 'Clima indisponível',
            'icon' => 'heroicon-o-cloud',
            'emoji' => '☁️',
            'badge_background' => '#E2E8F0',
            'city' => 'Ribeirão Preto',
        ];
    }

    /**
     * Converte código WMO em descrição em português
     */
    private function getWeatherDescription(int $code): string
    {
        return match (true) {
            $code === 0 => 'Céu limpo',
            $code >= 1 && $code <= 3 => 'Parcialmente nublado',
            $code >= 45 && $code <= 48 => 'Neblina',
            $code >= 51 && $code <= 55 => 'Garoa',
            $code >= 61 && $code <= 65 => 'Chuva',
            $code >= 71 && $code <= 77 => 'Neve',
            $code >= 80 && $code <= 82 => 'Pancadas de chuva',
            $code >= 95 && $code <= 99 => 'Tempestade',
            default => 'Indefinido',
        };
    }

    /**
     * Retorna ícone apropriado baseado no código do clima
     */
    private function getWeatherIcon(int $code): string
    {
        return match (true) {
            $code === 0 => 'heroicon-o-sun',
            $code >= 1 && $code <= 3 => 'heroicon-o-cloud',
            $code >= 45 && $code <= 48 => 'heroicon-o-cloud',
            $code >= 51 && $code <= 82 => 'heroicon-o-cloud',
            $code >= 95 && $code <= 99 => 'heroicon-o-bolt',
            default => 'heroicon-o-cloud',
        };
    }

    /**
     * Retorna emoji e cor de fundo para o badge do clima
     */
    private function getWeatherBadge(int $code): array
    {
        return match (true) {
            $code === 0 => ['emoji' => '☀️', 'background' => '#FDE68A'],
            $code >= 1 && $code <= 3 => ['emoji' => '🌤️', 'background' => '#FBE6A2'],
            $code >= 45 && $code <= 48 => ['emoji' => '🌫️', 'background' => '#E5E7EB'],
            $code >= 51 && $code <= 65 => ['emoji' => '🌧️', 'background' => '#BFDBFE'],
            $code >= 71 && $code <= 77 => ['emoji' => '❄️', 'background' => '#E0F2FE'],
            $code >= 80 && $code <= 82 => ['emoji' => '🌦️', 'background' => '#BFDBFE'],
            $code >= 95 && $code <= 99 => ['emoji' => '⛈️', 'background' => '#FECACA'],
            default => ['emoji' => '☁️', 'background' => '#E5E7EB'],
        };
    }

    /**
     * Obtém saudação baseada no horário
     */
    public function getGreeting(): string
    {
        $hour = now()->hour;
        $userName = auth()->user()->name ?? 'Usuário';
        $firstName = explode(' ', $userName)[0];

        return match (true) {
            $hour >= 5 && $hour < 12 => "Bom dia, {$firstName}!",
            $hour >= 12 && $hour < 18 => "Boa tarde, {$firstName}!",
            default => "Boa noite, {$firstName}!",
        };
    }

    /**
     * Data formatada em português
     */
    public function getFormattedDate(): string
    {
        return now()->translatedFormat('l, d \d\e F \d\e Y');
    }
}
