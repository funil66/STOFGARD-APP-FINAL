<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Header com ações --}}
        <div class="responsive-notification-header mb-4 md:mb-6">
            <div class="text-center sm:text-left">
                <h2 class="text-xl md:text-2xl font-bold" style="color: #1f2937;">Notificações</h2>
                <p class="text-xs md:text-sm" style="color: #6b7280;">Acompanhe todas as atualizações do sistema</p>
            </div>

            @php
                $unreadCount = \Illuminate\Notifications\DatabaseNotification::where('notifiable_type', \App\Models\User::class)
                    ->where('notifiable_id', auth()->id())
                    ->whereNull('read_at')
                    ->count();
            @endphp

            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="px-4 py-2 rounded-lg font-medium transition-all hover:scale-105"
                    style="background: #2563eb; color: white;">
                    Marcar todas como lidas
                </button>
            @endif
        </div>

        {{-- Lista de notificações --}}
        <div class="space-y-3">
            @forelse($this->getNotifications() as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'info';
                    $title = $data['title'] ?? $data['message'] ?? 'Notificação';
                    $message = $data['message'] ?? $data['body'] ?? '';
                    $module = $data['module'] ?? null;
                    $actionUrl = $data['action_url'] ?? $data['url'] ?? null;
                    $actionLabel = $data['action_label'] ?? 'Ver detalhes';
                    $isRead = !is_null($notification->read_at);
                    $colorMap = [
                        'success' => ['border' => '#10b981', 'bg' => '#d1fae5', 'text' => '#10b981'],
                        'warning' => ['border' => '#f59e0b', 'bg' => '#fed7aa', 'text' => '#f59e0b'],
                        'danger' => ['border' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#ef4444'],
                        'info' => ['border' => '#2563eb', 'bg' => '#dbeafe', 'text' => '#2563eb'],
                    ];
                    $colors = $colorMap[$type] ?? $colorMap['info'];
                @endphp
                <div wire:key="notification-{{ $notification->id }}"
                    class="rounded-xl p-5 transition-all hover:shadow-md border-l-4 {{ $isRead ? 'opacity-60' : '' }}"
                    style="background: white; border-left-color: {{ $colors['border'] }};">

                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1">
                            {{-- Ícone --}}
                            <div class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center"
                                style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }};">
                                @if($type === 'success')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg>
                                @elseif($type === 'warning')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                                    </svg>
                                @elseif($type === 'danger')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                @endif
                            </div>

                            {{-- Conteúdo --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold" style="color: #1f2937;">
                                        {{ $title }}
                                    </h3>
                                    @if($module)
                                        <span class="px-2 py-1 rounded text-xs font-medium"
                                            style="background: #f3f4f6; color: #6b7280;">
                                            {{ ucfirst($module) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-sm mb-2" style="color: #6b7280;">
                                    {{ $message }}
                                </p>

                                <div class="flex items-center gap-4 text-xs" style="color: #9ca3af;">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>

                                    @if(!$isRead)
                                        <button wire:click="markAsRead('{{ $notification->id }}')" class="hover:underline">
                                            Marcar como lida
                                        </button>
                                    @endif

                                    @if($actionUrl)
                                        <a href="{{ $actionUrl }}" class="hover:underline font-medium" style="color: #2563eb;">
                                            {{ $actionLabel }} →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Ações --}}
                        <div class="flex items-center gap-2">
                            @if(!$isRead)
                                <div class="h-3 w-3 rounded-full" style="background: #2563eb;"></div>
                            @endif

                            <button wire:click="deleteNotification('{{ $notification->id }}')"
                                class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" style="color: #9ca3af;" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 rounded-xl" style="background: white;">
                    <svg class="mx-auto h-16 w-16 mb-4" style="color: #d1d5db;" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-2" style="color: #6b7280;">Nenhuma notificação</h3>
                    <p class="text-sm" style="color: #9ca3af;">Você está em dia com todas as atualizações!</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>