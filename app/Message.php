<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Message
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
    public static function collectMessages()
    {
        $messages = Cache::pull('messages_' . Auth::id());

        return empty($messages) ? [] : $messages;
    }

    /**
     * записываем сообщение в кеш, оно отобразиться при следующем ajax запросе или при обновлении страницы
     *
     * @param $message
     * @param string $type
     */
    public static function put($message, $type = self::TYPE_ERROR)
    {
        $key = 'messages_' . Auth::id();

        $messages = Cache::get($key);

        $messages[] = ['type' => $type, 'text' => $message];

        Cache::put($key, $messages, 60);
    }
}
