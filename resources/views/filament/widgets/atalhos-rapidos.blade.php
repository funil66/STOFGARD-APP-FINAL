<div class="grid grid-cols-3 gap-4">
    @foreach($links as $link)
        <a href="{{ $link['url'] }}" class="flex items-center justify-center p-4 rounded-lg text-white {{ $link['color'] }} hover:opacity-90" style="min-height:80px">
            <div class="flex items-center gap-3">
                <x-heroicon-o-chevron-right class="w-5 h-5 opacity-80" />
                <div class="text-lg font-semibold">{{ $link['label'] }}</div>
            </div>
        </a>
    @endforeach
</div>
