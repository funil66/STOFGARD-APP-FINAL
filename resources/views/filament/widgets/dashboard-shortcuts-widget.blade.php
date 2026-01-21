<div class="py-4">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($this->getModules() as $module)
                <div class="bg-white fw-shortcut-card rounded-[20px] shadow-sm hover:shadow-md transition-shadow border border-gray-100 p-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 flex items-center justify-center rounded-[16px]" style="background-color: {{ $module['icon_background'] }}20;">
                                @svg($module['icon'], 'w-7 h-7', ['style' => 'color: ' . $module['icon_background']])
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">{{ $module['name'] }}</h3>
                                @if(isset($module['description']))
                                    <p class="text-xs text-gray-500">{{ $module['description'] }}</p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ $module['route'] }}" class="text-xs font-medium text-brand-primary bg-primary-50 border border-primary-200 rounded-lg px-3 py-1.5 hover:bg-primary-100">Abrir</a>
                    </div>

                    @if(isset($module['submodules']))
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($module['submodules'] as $submenu)
                                <a href="{{ $submenu['route'] }}" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition">
                                    @svg($submenu['icon'], 'w-5 h-5 text-gray-600')
                                    <span class="text-xs text-gray-700 font-medium">{{ $submenu['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
