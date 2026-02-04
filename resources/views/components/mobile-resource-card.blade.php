{{-- 
    Componente: Mobile Resource Card
    Uso: Substituir tabelas por cards em dispositivos móveis
    
    Propriedades:
    - title: Título principal (ex: ORC-001)
    - subtitle: Linha secundária (ex: João Silva)
    - status: Texto do status
    - statusColor: Cor Filament (success, danger, warning, info, gray)
    - icon: Ícone Heroicon
    - iconColor: Cor hexadecimal do ícone
    - value: Valor em destaque (ex: R$ 450,00)
    - valueColor: Cor do valor (success, danger)
    - items: Array de itens adicionais [{icon, label, value}]
    - actions: Slot para ações
--}}

@props([
    'title' => '',
    'subtitle' => null,
    'status' => null,
    'statusColor' => 'gray',
    'icon' => 'heroicon-o-document',
    'iconColor' => '#3b82f6',
    'value' => null,
    'valueColor' => 'success',
    'items' => [],
    'url' => null,
    'recordId' => null,
])

@php
    $statusClasses = match($statusColor) {
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
        'primary' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400',
        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    };
    
    $valueClasses = match($valueColor) {
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        default => 'text-gray-900 dark:text-white',
    };
@endphp

<div class="mobile-resource-card group bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 transition-all duration-200 overflow-hidden"
    @if($recordId) data-record-id="{{ $recordId }}" @endif>
    
    {{-- HEADER: Ícone + Título + Status --}}
    <div class="mobile-card-header flex items-center gap-3 p-3 border-b border-gray-100 dark:border-gray-700">
        {{-- Ícone Principal --}}
        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" 
            style="background-color: {{ $iconColor }}15;">
            <x-dynamic-component :component="$icon" class="w-5 h-5" style="color: {{ $iconColor }};" />
        </div>
        
        {{-- Título e Subtítulo --}}
        <div class="flex-1 min-w-0">
            @if($url)
                <a href="{{ $url }}" class="block">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                        {{ $title }}
                    </h3>
                </a>
            @else
                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                    {{ $title }}
                </h3>
            @endif
            
            @if($subtitle)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
        
        {{-- Badge de Status --}}
        @if($status)
            <span class="flex-shrink-0 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide rounded-full {{ $statusClasses }}">
                {{ $status }}
            </span>
        @endif
    </div>
    
    {{-- BODY: Valor em Destaque + Items Adicionais --}}
    <div class="mobile-card-body p-3">
        {{-- Valor Principal em Destaque --}}
        @if($value)
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Valor</span>
                <span class="text-lg font-bold {{ $valueClasses }}">{{ $value }}</span>
            </div>
        @endif
        
        {{-- Itens Secundários em Grid --}}
        @if(count($items) > 0)
            <div class="grid grid-cols-2 gap-2">
                @foreach($items as $item)
                    <div class="flex items-center gap-1.5 text-xs">
                        @if(isset($item['icon']))
                            <x-dynamic-component :component="$item['icon']" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                        @endif
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['label'] ?? '' }}:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $item['value'] ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    
    {{-- FOOTER: Ações --}}
    @if(isset($actions) && $actions->isNotEmpty())
        <div class="mobile-card-footer flex items-center justify-end gap-1 px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            {{ $actions }}
        </div>
    @endif
</div>
