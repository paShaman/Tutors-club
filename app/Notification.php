<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Notification
{
    const TYPE_ERROR    = 'error';
    const TYPE_SUCCESS  = 'success';
    const TYPE_WARNING  = 'warning';
    const TYPE_INFO     = 'info';

    /**
     * собираем сообщения которые надо отобразить пользователю
     *
     * @return array
     */
    public static function collectNotifications()
    {
        $notifications = Cache::pull('notifications_' . Auth::id());

        return empty($notifications) ? [] : $notifications;
    }

    /**
     * записываем сообщение в кеш, оно отобразиться при следующем ajax запросе или при обновлении страницы
     *
     * @param $notification
     * @param string $type
     */
    public static function put($notification, $type = self::TYPE_ERROR)
    {
        $key = 'notifications_' . Auth::id();

        $notifications = Cache::get($key);

        $notifications[] = ['type' => $type, 'text' => $notification];

        Cache::put($key, $notifications, 60);
    }
}
