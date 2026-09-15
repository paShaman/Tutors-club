<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Вырезает секреты ботов из текста перед записью в лог.
 *
 * Guzzle кладёт в текст исключения полный URL, а у Telegram токен стоит прямо
 * в пути (`/bot<TOKEN>/sendMessage`), поэтому без вырезания токен утекает в
 * storage/logs при первом же обрыве соединения. Отдельно маскируются учётные
 * данные прокси: в ошибке соединения остаётся хост, но не логин с паролем.
 */
final class LogScrubber
{
    /** Короче этого значения вырезать нельзя: затрёт обычный текст. */
    private const MIN_SECRET_LENGTH = 8;

    private const PLACEHOLDER = '***';

    public static function scrub(string $message): string
    {
        foreach (self::secrets() as $secret) {
            if (mb_strlen($secret) < self::MIN_SECRET_LENGTH) {
                continue;
            }

            $message = str_replace($secret, self::PLACEHOLDER, $message);
        }

        return self::maskUrlCredentials($message);
    }

    /**
     * @return array<int, string>
     */
    private static function secrets(): array
    {
        // Прокси здесь нет намеренно: от него важна маскировка учётных данных
        // (maskUrlCredentials), а хост в ошибке соединения полезен для отладки.
        return [
            (string) config('services.telegram.bot_token'),
            (string) config('services.telegram.webhook_secret'),
            (string) config('services.telegram_students.bot_token'),
            (string) config('services.telegram_students.webhook_secret'),
            (string) config('services.max.bot_token'),
            (string) config('services.max.webhook_secret'),
        ];
    }

    /**
     * Логин и пароль внутри URL: `://user:pass@host` → `://***@host`.
     *
     * Хост оставляем: по нему ищут проблему, а учётные данные в логе не нужны.
     */
    private static function maskUrlCredentials(string $message): string
    {
        $masked = preg_replace('~://[^\s/@]+:[^\s/@]+@~', '://' . self::PLACEHOLDER . '@', $message);

        return $masked ?? $message;
    }
}
