<div class="py-4">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach ($this->getShortcuts() as $shortcut)
                <a href="{{ $shortcut['url'] }}" class="group block bg-white rounded-lg shadow-sm hover:shadow-md border border-gray-100 transition p-4">
                    <div class="flex items-center gap-4">
                        <div class="{{ $shortcut['color'] }} p-3 rounded-lg text-white w-12 h-12 flex items-center justify-center">
                            @svg($shortcut['icon'], 'w-6 h-6')
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $shortcut['label'] }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
