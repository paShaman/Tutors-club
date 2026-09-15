<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\User;
use App\Support\CacheKeys;
use App\Support\LogScrubber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Доставка обращений и системных уведомлений владельцу в Telegram и MAX.
 *
 * Какой бот основной, задаёт FEEDBACK_PRIMARY_BOT (по умолчанию telegram):
 * в него уходят сообщения из формы кабинета и в него же пересылаются
 * сообщения, написанные боту в другом мессенджере. Если основной бот
 * не настроен, используется резервный, чтобы обращения не терялись.
 */
final class FeedbackBotService
{
    public const TELEGRAM = 'telegram';
    public const MAX = 'max';

    private const MAX_API = 'https://platform-api2.max.ru';

    public function primary(): string
    {
        return strtolower((string) config('services.feedback.primary')) === self::MAX ? self::MAX : self::TELEGRAM;
    }

    public function telegramConfigured(): bool
    {
        return $this->telegramToken() !== '' && $this->telegramChatId() !== '';
    }

    public function maxConfigured(): bool
    {
        return $this->maxToken() !== '' && $this->maxOwnerId() !== '';
    }

    public function configured(string $bot): bool
    {
        return $bot === self::MAX ? $this->maxConfigured() : $this->telegramConfigured();
    }

    /** Бот для доставки: основной, либо настроенный резервный. */
    public function deliveryBot(): ?string
    {
        $primary = $this->primary();

        if ($this->configured($primary)) {
            return $primary;
        }

        $fallback = $primary === self::MAX ? self::TELEGRAM : self::MAX;

        return $this->configured($fallback) ? $fallback : null;
    }

    public function telegramBotUrl(): ?string
    {
        $username = $this->botUsername('services.telegram.bot_username');

        return $username !== null ? 'https://t.me/' . $username : null;
    }

    public function maxBotUrl(): ?string
    {
        $username = $this->botUsername('services.max.bot_username');

        return $username !== null ? 'https://max.ru/' . $username : null;
    }

    public function telegramChatId(): string
    {
        return (string) config('services.telegram.chat_id');
    }

    public function maxOwnerId(): string
    {
        return (string) config('services.max.owner_id');
    }

    /**
     * Отправляет владельцу текст и, при наличии, изображения.
     *
     * Изображения — либо загруженные файлы (из формы кабинета), либо прямые
     * ссылки (например, вложение из другого мессенджера).
     *
     * @param  array<int, UploadedFile|string>  $photos
     */
    public function send(string $bot, string $text, array $photos = []): bool
    {
        if (! $this->configured($bot)) {
            return false;
        }

        return $bot === self::MAX
            ? $this->sendToMax($text, $photos)
            : $this->sendToTelegram($text, $photos);
    }

    /**
     * Уведомляет владельца о регистрации нового пользователя.
     *
     * @param  string|null  $source  источник регистрации (сайт или название соцсети)
     */
    public function notifyRegistration(User $user, ?string $source = null): bool
    {
        $bot = $this->deliveryBot();

        if ($bot === null) {
            return false;
        }

        $name = (string) $user->name;

        $lines = [
            '🆕 Новый пользователь',
            '',
            '👤 Имя: ' . ($name !== '' ? $name : '—'),
            '📧 Email: ' . ($user->email ?: '—'),
            '🆔 ID: ' . $user->id,
        ];

        if ($source !== null && $source !== '') {
            $lines[] = '📍 Источник: ' . $source;
        }

        $lines[] = '';
        $lines[] = '🕒 ' . now()->format('Y-m-d H:i');

        return $this->send($bot, mb_substr(implode("\n", $lines), 0, 4000));
    }

    /** Прямая ссылка на файл Telegram — нужна, чтобы переслать фото в MAX. */
    public function telegramPhotoUrl(string $fileId): ?string
    {
        $cached = Cache::get(CacheKeys::telegramFile($fileId));

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->telegramToken();

        try {
            $response = Http::withOptions($this->telegramProxyOptions())
                ->connectTimeout(5)
                ->timeout(10)
                ->get("https://api.telegram.org/bot{$token}/getFile", [
                    'file_id' => $fileId,
                ]);

            $path = $response->json('result.file_path');

            if (! $response->successful() || ! is_string($path) || $path === '') {
                Log::error('Telegram getFile: не удалось получить файл', [
                    'status'      => $response->status(),
                    'description' => $response->json('description'),
                ]);

                return null;
            }

            $url = "https://api.telegram.org/file/bot{$token}/{$path}";

            Cache::put(CacheKeys::telegramFile($fileId), $url, now()->addHour());

            return $url;
        } catch (Throwable $e) {
            Log::error('Telegram getFile: запрос не выполнен', ['error' => LogScrubber::scrub($e->getMessage())]);

            return null;
        }
    }

    /**
     * @param  array<int, UploadedFile|string>  $photos
     */
    private function sendToTelegram(string $text, array $photos): bool
    {
        $token  = $this->telegramToken();
        $chatId = $this->telegramChatId();

        try {
            $response = Http::asJson()
                ->withOptions($this->telegramProxyOptions())
                ->connectTimeout(5)
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful()) {
                Log::error('Telegram sendMessage: сообщение не отправлено', [
                    'status'      => $response->status(),
                    'error_code'  => $response->json('error_code'),
                    'description' => $response->json('description'),
                ]);

                return false;
            }

            foreach ($photos as $photo) {
                $sent = $photo instanceof UploadedFile
                    ? Http::withOptions($this->telegramProxyOptions())
                        ->connectTimeout(5)
                        ->timeout(30)
                        ->attach('photo', (string) file_get_contents($photo->getPathname()), $photo->getClientOriginalName())
                        ->post("https://api.telegram.org/bot{$token}/sendPhoto", ['chat_id' => $chatId])
                    : Http::asJson()
                        ->withOptions($this->telegramProxyOptions())
                        ->connectTimeout(5)
                        ->timeout(30)
                        ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                            'chat_id' => $chatId,
                            'photo'   => $photo,
                        ]);

                if (! $sent->successful()) {
                    Log::error('Telegram sendPhoto: фото не отправлено', [
                        'status'      => $sent->status(),
                        'description' => $sent->json('description'),
                    ]);

                    return false;
                }
            }
        } catch (Throwable $e) {
            Log::error('Telegram sendMessage: запрос не выполнен', ['error' => LogScrubber::scrub($e->getMessage())]);

            return false;
        }

        return true;
    }

    /**
     * @param  array<int, UploadedFile|string>  $photos
     */
    private function sendToMax(string $text, array $photos): bool
    {
        $token   = $this->maxToken();
        $ownerId = $this->maxOwnerId();

        try {
            $attachments = $this->maxAttachments($token, $photos);

            $body = $attachments === []
                ? ['text' => $text]
                : ['text' => $text, 'attachments' => $attachments];

            // MAX обрабатывает загруженные вложения асинхронно, поэтому пробуем повторно.
            if ($this->maxPost($token, $ownerId, $body, retryOnNotReady: $attachments !== [])) {
                return true;
            }

            // Если вложение так и не обработалось — доставляем хотя бы текст.
            return $attachments === [] || $this->maxPost($token, $ownerId, ['text' => $text]);
        } catch (Throwable $e) {
            Log::error('MAX sendMessage: запрос не выполнен', ['error' => LogScrubber::scrub($e->getMessage())]);

            return false;
        }
    }

    /**
     * Готовит вложения MAX: файлы загружает, ссылки передаёт как есть.
     *
     * @param  array<int, UploadedFile|string>  $photos
     * @return array<int, array{type: string, payload: array<string, string>}>
     */
    private function maxAttachments(string $token, array $photos): array
    {
        $attachments = [];

        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                $attachments[] = ['type' => 'image', 'payload' => ['url' => $photo]];
                continue;
            }

            $attachment = $this->maxUploadImage($token, $photo);

            if ($attachment !== null) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function maxPost(string $token, string $ownerId, array $body, bool $retryOnNotReady = false): bool
    {
        $attempts = $retryOnNotReady ? 4 : 1;
        $url      = self::MAX_API . '/messages?user_id=' . urlencode($ownerId);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asJson()
                ->withOptions($this->maxProxyOptions())
                ->connectTimeout(5)
                ->timeout(15)
                ->post($url, $body);

            if ($response->successful()) {
                return true;
            }

            $notReady = $response->json('code') === 'attachment.not.ready';

            if (! $retryOnNotReady || ! $notReady || $attempt === $attempts) {
                Log::error('MAX sendMessage: сообщение не отправлено', [
                    'attempt' => $attempt,
                    'status'  => $response->status(),
                    'code'    => $response->json('code'),
                    'message' => $response->json('message'),
                ]);

                return false;
            }

            usleep(300_000 * $attempt);
        }

        return false;
    }

    /**
     * @return array{type: string, payload: array{token: string}}|null
     */
    private function maxUploadImage(string $token, UploadedFile $photo): ?array
    {
        $slot = Http::withHeaders(['Authorization' => $token])
            ->withOptions($this->maxProxyOptions())
            ->connectTimeout(5)
            ->timeout(15)
            ->post(self::MAX_API . '/uploads?type=image');

        $uploadUrl = $slot->json('url');

        if (! $slot->successful() || ! is_string($uploadUrl) || $uploadUrl === '') {
            Log::error('MAX uploads: не удалось получить слот для загрузки', [
                'status'  => $slot->status(),
                'message' => $slot->json('message'),
            ]);

            return null;
        }

        $uploaded = Http::withOptions($this->maxProxyOptions())
            ->connectTimeout(5)
            ->timeout(30)
            ->attach('data', (string) file_get_contents($photo->getPathname()), $photo->getClientOriginalName())
            ->post($uploadUrl);

        $attachmentToken = $uploaded->json('token');

        if (! is_string($attachmentToken) || $attachmentToken === '') {
            Log::error('MAX uploads: файл не загружен', [
                'status' => $uploaded->status(),
            ]);

            return null;
        }

        return ['type' => 'image', 'payload' => ['token' => $attachmentToken]];
    }

    private function telegramToken(): string
    {
        return (string) config('services.telegram.bot_token');
    }

    /**
     * Опции Guzzle для api.telegram.org.
     *
     * Прокси не задан — опция не передаётся вовсе: соединение идёт напрямую
     * ровно так же, как до появления прокси (и не перебивает переменные
     * окружения хостинга вроде HTTPS_PROXY, которые читает сам Guzzle).
     *
     * @return array<string, mixed>
     */
    private function telegramProxyOptions(): array
    {
        return $this->proxyOptions('services.telegram.proxy');
    }

    /**
     * Опции Guzzle для MAX. По умолчанию пусто: MAX обычно доступен напрямую.
     *
     * @return array<string, mixed>
     */
    private function maxProxyOptions(): array
    {
        return $this->proxyOptions('services.max.proxy');
    }

    /**
     * @return array<string, mixed>
     */
    private function proxyOptions(string $key): array
    {
        $proxy = trim((string) config($key));

        return $proxy !== '' ? ['proxy' => $proxy] : [];
    }

    private function maxToken(): string
    {
        return (string) config('services.max.bot_token');
    }

    private function botUsername(string $key): ?string
    {
        $username = ltrim((string) config($key), '@');

        return $username !== '' ? $username : null;
    }
}
