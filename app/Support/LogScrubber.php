<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Вырезает секреты ботов из текста перед записью в лог.
 *
 * Guzzle кладёт в текст исключения полный URL, а у Telegram токен стоит прямо
 * в пути (`/bot<TOKEN>/sendMessage`), поэтому без вырезания токен утекает в
 * storage/logs при первом же обрыве соединения.
 */
final class LogScrubber
{
    /** Короче этого значения вырезать нельзя: затрёт обычный текст. */
    private const MIN_SECRET_LENGTH = 8;

    private const PLACEHOLDER = '***';

    public static function scrub(string $message): string
    {
        $secrets = [
            (string) config('services.telegram.bot_token'),
            (string) config('services.telegram.webhook_secret'),
            (string) config('services.telegram_students.bot_token'),
            (string) config('services.telegram_students.webhook_secret'),
            (string) config('services.max.bot_token'),
            (string) config('services.max.webhook_secret'),
        ];

        foreach ($secrets as $secret) {
            if (mb_strlen($secret) < self::MIN_SECRET_LENGTH) {
                continue;
            }

            $message = str_replace($secret, self::PLACEHOLDER, $message);
        }

        return $message;
    }
}
