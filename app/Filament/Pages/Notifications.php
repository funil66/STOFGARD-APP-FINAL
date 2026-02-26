<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Notifications\DatabaseNotification;

class Notifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static string $view = 'filament.pages.notifications';

    protected static ?string $title = 'Notificações';

    protected static ?string $slug = 'notifications';

    // Não mostrar no menu lateral (já temos o botão no header)
    protected static bool $shouldRegisterNavigation = false;

    public function getNotifications()
    {
        return DatabaseNotification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function markAsRead($notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);

        if ($notification && $notification->notifiable_id == auth()->id()) {
            $notification->markAsRead();

            $data = $notification->data;
            if (!empty($data['action_url'])) {
                return redirect($data['action_url']);
            }
        }
    }

    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        $this->dispatch('notifications-updated');
    }

    public function deleteNotification($notificationId)
    {
        DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->delete();

        $this->dispatch('notifications-updated');
    }
}
