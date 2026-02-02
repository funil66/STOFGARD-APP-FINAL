<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class ClearWeatherCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:clear-cache {city?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa o cache de dados meteorológicos. Use: php artisan weather:clear-cache [cidade]';

    /**
     * Execute the console command.
     */
    public function handle(WeatherService $weatherService): int
    {
        $city = $this->argument('city');

        if ($city) {
            $weatherService->clearCityCache($city);
            $this->info("Cache da cidade '{$city}' foi limpo com sucesso!");
        } else {
            $weatherService->clearAllWeatherCache();
            $this->info("Comando de limpeza de cache executado. Para limpar totalmente, use: php artisan cache:clear");
        }

        return Command::SUCCESS;
    }
}
