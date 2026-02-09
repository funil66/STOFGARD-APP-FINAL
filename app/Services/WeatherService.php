<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WeatherService
{
    private const CACHE_TTL = 1800; // 30 minutos (em segundos)

    private const API_TIMEOUT = 5; // Timeout de 5 segundos

    private const ERROR_CACHE_TTL = 300; // 5 minutos para cachear erros 404

    /**
     * Busca dados meteorológicos para uma cidade específica.
     * Usa cache dinâmico por cidade.
     */
    public function getWeatherByCity(string $city): ?array
    {
        $apiKey = config('services.openweather.api_key');
        $defaultCity = config('services.openweather.default_city', 'London');

        // Se a API key não estiver configurada, retorna null
        if (empty($apiKey)) {
            Log::warning('OpenWeather API key not configured');

            return null;
        }

        // Normaliza o nome da cidade para a chave do cache
        $cacheKey = $this->generateCacheKey($city);

        try {
            // Tenta buscar do cache primeiro
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($city, $apiKey) {
                return $this->fetchFromApi($city, $apiKey);
            });
        } catch (\Exception $e) {
            Log::error('Weather Service Exception', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            // Retorna null silenciosamente para não quebrar a aplicação
            return null;
        }
    }

    /**
     * Faz a requisição para a API do OpenWeather.
     */
    private function fetchFromApi(string $city, string $apiKey): ?array
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get('https://api.openweathermap.org/data/2.5/weather', [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric', // Celsius
                    'lang' => 'pt_br',   // Descrições em português
                ]);

            // Se a API retornou erro
            if ($response->failed()) {
                if ($response->status() === 404) {
                    Log::info('City not found on OpenWeather', ['city' => $city]);

                    // Cachear erro 404 por menos tempo para evitar requisições repetidas
                    Cache::put($this->generateCacheKey($city).'_error', true, self::ERROR_CACHE_TTL);
                }

                return null;
            }

            $data = $response->json();

            // Valida se os dados essenciais existem
            if (! isset($data['main']) || ! isset($data['weather'][0])) {
                return null;
            }

            // Monta o DTO (Data Transfer Object) limpo
            return $this->buildWeatherDto($data);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenWeather API Connection Timeout', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenWeather API Unexpected Error', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Constrói um DTO padronizado com apenas os dados necessários.
     */
    private function buildWeatherDto(array $rawData): array
    {
        return [
            'city' => $rawData['name'] ?? 'Unknown',
            'country' => $rawData['sys']['country'] ?? '',
            'temperature' => round($rawData['main']['temp'] ?? 0, 1),
            'feels_like' => round($rawData['main']['feels_like'] ?? 0, 1),
            'description' => ucfirst($rawData['weather'][0]['description'] ?? 'N/A'),
            'humidity' => $rawData['main']['humidity'] ?? 0,
            'icon' => $rawData['weather'][0]['icon'] ?? '01d',
            'icon_url' => $this->getIconUrl($rawData['weather'][0]['icon'] ?? '01d'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Retorna a URL do ícone do OpenWeather.
     */
    private function getIconUrl(string $iconCode): string
    {
        return "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
    }

    /**
     * Gera uma chave de cache única por cidade.
     */
    private function generateCacheKey(string $city): string
    {
        return 'weather_data_'.Str::slug(strtolower($city));
    }

    /**
     * Limpa o cache de uma cidade específica (útil para admin).
     */
    public function clearCityCache(string $city): void
    {
        $cacheKey = $this->generateCacheKey($city);
        Cache::forget($cacheKey);
        Cache::forget($cacheKey.'_error');
    }

    /**
     * Limpa todo o cache de clima.
     */
    public function clearAllWeatherCache(): void
    {
        // Como usamos chaves dinâmicas, não podemos limpar tudo facilmente
        // Esta função seria útil se implementássemos um índice de cidades cacheadas
        Log::info('Weather cache clearing requested (manual flush may be needed)');
    }
}
