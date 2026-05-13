<?php

declare(strict_types=1);

namespace App;

class Notification
{
    /**
     * @var array<int, string>
     */
    private static array $messages = [];

    /**
     * Add a notification message.
     */
    public static function put(string $message): void
    {
        self::$messages[] = $message;
    }

    /**
     * Get all collected notification messages and clear them.
     *
     * @return array<int, string>
     */
    public static function collectNotifications(): array
    {
        $messages = self::$messages;
        self::$messages = [];

        return $messages;
    }
}