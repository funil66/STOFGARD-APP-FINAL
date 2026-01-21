<?php

namespace App\Filament\Pages;

use App\Models\Notification;
use Filament\Pages\Page;

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
        return Notification::forUser(auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function markAsRead($notificationId)
    {
        $notification = Notification::find($notificationId);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->markAsRead();

            if ($notification->action_url) {
                return redirect($notification->action_url);
            }
        }
    }

    public function markAllAsRead()
    {
        Notification::forUser(auth()->id())
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        $this->dispatch('notifications-updated');
    }

    public function deleteNotification($notificationId)
    {
        Notification::where('id', $notificationId)
            ->where('user_id', auth()->id())
            ->delete();

        $this->dispatch('notifications-updated');
    }
}
