<x-filament::widget>
    <x-filament::card>
        <div
            class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl text-white shadow-lg">
            <div class="flex items-center gap-6">
                <!-- Saudação -->
                <div class="border-r border-white/20 pr-6 mr-2">
                    <h1 class="text-2xl font-bold">{{ $greeting }}, {{ explode(' ', $userName)[0] }}!</h1>
                    <p class="text-sm opacity-90">Bom trabalho hoje 🚀</p>
                </div>

                <!-- Clima -->
                <div class="flex items-center gap-4">
                    <div class="text-5xl animate-pulse">
                        {{ $icon ?? '⛅' }}
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold">{{ $temp ?? '--' }}°C</h2>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium opacity-90">{{ $condition ?? 'Carregando...' }}</span>
                            <span class="text-xs opacity-75 flex items-center gap-1">
                                <x-heroicon-o-map-pin class="w-3 h-3" />
                                {{ $city }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:flex flex-col gap-2 text-right">
                <div class="flex items-center justify-end gap-2">
                    <span class="text-sm opacity-80">Umidade</span>
                    <span class="font-bold flex items-center gap-1">
                        💧 {{ $humidity ?? '--' }}%
                    </span>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <span class="text-sm opacity-80">Vento</span>
                    <span class="font-bold flex items-center gap-1">
                        🌬️ {{ $wind ?? '--' }} km/h
                    </span>
                </div>
                <div class="text-xs opacity-60 mt-2">
                    Atualizado agorinha
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>