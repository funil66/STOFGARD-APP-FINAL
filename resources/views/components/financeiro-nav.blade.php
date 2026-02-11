{{-- #12: Sticky navigation menu for all /financeiros/* pages --}}
@php
    $currentRoute = request()->route()?->getName() ?? '';
    $links = [
        ['route' => 'filament.admin.resources.financeiros.create', 'label' => '+ Nova Transação', 'icon' => '💰', 'color' => 'primary'],
        ['route' => 'filament.admin.resources.financeiros.index', 'label' => 'Todas', 'icon' => '📋', 'color' => ''],
        ['route' => 'filament.admin.resources.financeiros.receitas', 'label' => 'Receitas', 'icon' => '📈', 'color' => 'success'],
        ['route' => 'filament.admin.resources.financeiros.despesas', 'label' => 'Despesas', 'icon' => '📉', 'color' => 'danger'],
        ['route' => 'filament.admin.resources.financeiros.pendentes', 'label' => 'Pendentes', 'icon' => '⏳', 'color' => 'warning'],
        ['route' => 'filament.admin.resources.financeiros.atrasadas', 'label' => 'Atrasadas', 'icon' => '🔴', 'color' => 'danger'],
        ['route' => 'filament.admin.resources.financeiros.dashboard', 'label' => 'Gráficos', 'icon' => '📊', 'color' => ''],
        ['route' => 'filament.admin.resources.financeiros.analise', 'label' => 'Análise', 'icon' => '🔍', 'color' => ''],
        ['route' => 'filament.admin.resources.financeiros.extratos', 'label' => 'Extratos', 'icon' => '📄', 'color' => ''],
        ['route' => 'filament.admin.resources.financeiros.comissoes', 'label' => 'Comissões', 'icon' => '🤝', 'color' => ''],
    ];
@endphp

<div
    class="sticky top-16 z-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 -mx-4 px-4 mb-4 overflow-x-auto">
    <div class="flex items-center gap-1 py-2 min-w-max">
        @foreach($links as $link)
            @php
                $isActive = $currentRoute === $link['route'];
                $activeClasses = $isActive
                    ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border-primary-500 font-semibold'
                    : 'text-gray-600 dark:text-gray-400 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200';
            @endphp
            <a href="{{ route($link['route']) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs border transition-all duration-150 whitespace-nowrap {{ $activeClasses }}">
                <span>{{ $link['icon'] }}</span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>