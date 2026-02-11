<div>
    <div class="mb-6">
        {{ $this->form }}
    </div>

    <div
        class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-4 dark:bg-gray-900 dark:ring-white/10 relative min-h-[400px]">

        <div wire:loading.flex
            class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 z-10">
            <x-filament::loading-indicator class="h-10 w-10 text-primary-500" />
        </div>

        <div 
             x-load
             x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
             x-data="chart({
                 cachedData: @js($this->getChartData()),
                 options: @js($this->getOptions()),
                 type: @js($this->getChartType()),
             })"
             class="h-full w-full min-h-[300px]"
        >
             <canvas x-ref="canvas"></canvas>
             <span x-ref="backgroundColorElement" class="text-primary-50 dark:text-primary-400/10"></span>
             <span x-ref="borderColorElement" class="text-primary-500 dark:text-primary-400"></span>
             <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
             <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
        </div>
    </div>
</div>