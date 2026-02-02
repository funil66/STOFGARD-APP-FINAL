<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Endpoint público para buscar dados meteorológicos.
     * 
     * @param WeatherRequest $request
     * @return JsonResponse
     */
    public function getWeather(WeatherRequest $request): JsonResponse
    {
        $city = $request->getSanitizedCity();

        // Busca dados do serviço (com cache)
        $weatherData = $this->weatherService->getWeatherByCity($city);

        // Se não encontrou dados (cidade inválida ou API offline)
        if ($weatherData === null) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível obter dados meteorológicos. Verifique o nome da cidade ou tente novamente mais tarde.',
                'error_code' => 'WEATHER_UNAVAILABLE',
            ], 503); // Service Unavailable
        }

        // Retorna dados com sucesso
        return response()->json([
            'success' => true,
            'data' => $weatherData,
            'cached' => true, // Sempre assume que pode estar cacheado
        ], 200);
    }
}
