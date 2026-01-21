@php($weather = $this->getWeatherData())

<div class="rounded-[24px] text-white px-8 py-5" style="background: linear-gradient(90deg, #1E6DD8 0%, #22A1DC 100%);">
    <div class="flex items-center justify-between">
        {{-- Saudação à esquerda --}}
        <div>
            <h1 class="text-2xl font-bold">
                {{ $this->getGreeting() }}
            </h1>
            <p class="text-sm text-white/90 mt-1">
                Hoje é {{ $this->getFormattedDate() }}
            </p>
        </div>

        {{-- Texto centralizado --}}
        <div class="text-center">
            <p class="text-base font-semibold tracking-widest" style="font-family: 'Segoe UI', sans-serif;">
                𝘿𝙀𝙐𝙎 𝙎𝙀𝙅𝘼 𝙇𝙊𝙐𝙑𝘼𝘿𝙊
            </p>
        </div>

        {{-- Widget clima à direita --}}
        <div class="flex items-center gap-3 bg-white/20 rounded-[20px] px-5 py-3">
            <span class="text-3xl">{{ $weather['emoji'] }}</span>
            <div class="text-right">
                <div class="text-xl font-bold">{{ $weather['temperature'] }}°C</div>
                <div class="text-xs text-white/80">{{ $weather['city'] }}</div>
                <div class="text-xs text-white/90">{{ $weather['description'] }}</div>
            </div>
        </div>
    </div>
</div>
