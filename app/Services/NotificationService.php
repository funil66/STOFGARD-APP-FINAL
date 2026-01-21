<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Criar notificação para um usuário
     */
    public static function create(
        User|int $user,
        string $type,
        string $title,
        string $message,
        ?string $module = null,
        ?string $icon = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'module' => $module,
            'icon' => $icon ?? 'heroicon-o-bell',
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
        ]);
    }

    /**
     * Criar notificação de sucesso
     */
    public static function success(User|int $user, string $title, string $message, ?string $module = null): Notification
    {
        return self::create($user, 'success', $title, $message, $module, 'heroicon-o-check-circle');
    }

    /**
     * Criar notificação de informação
     */
    public static function info(User|int $user, string $title, string $message, ?string $module = null): Notification
    {
        return self::create($user, 'info', $title, $message, $module, 'heroicon-o-information-circle');
    }

    /**
     * Criar notificação de aviso
     */
    public static function warning(User|int $user, string $title, string $message, ?string $module = null): Notification
    {
        return self::create($user, 'warning', $title, $message, $module, 'heroicon-o-exclamation-triangle');
    }

    /**
     * Criar notificação de erro
     */
    public static function danger(User|int $user, string $title, string $message, ?string $module = null): Notification
    {
        return self::create($user, 'danger', $title, $message, $module, 'heroicon-o-x-circle');
    }

    /**
     * Obter notificações não lidas de um usuário
     */
    public static function getUnreadCount(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::forUser($userId)->unread()->count();
    }

    /**
     * Marcar todas como lidas
     */
    public static function markAllAsRead(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        Notification::forUser($userId)->unread()->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Criar notificação para todos os administradores
     */
    public static function notifyAdmins(string $type, string $title, string $message, ?string $module = null): void
    {
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            self::create($admin, $type, $title, $message, $module);
        }
    }
}
