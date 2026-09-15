<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FeedbackBotService;
use App\Support\CacheKeys;
use App\Support\LogScrubber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackBotService $bots)
    {
    }

    /**
     * Пересылает обращение из кабинета владельцу в основной бот обратной связи.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact'  => ['required', 'string', 'max:255'],
            'message'  => ['required', 'string', 'max:5000'],
            'photos'   => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ], [
            'photos.max'     => lng('error.feedback_photo_limit'),
            'photos.*.image' => lng('error.feedback_photo_invalid'),
            'photos.*.mimes' => lng('error.feedback_photo_invalid'),
            'photos.*.max'   => lng('error.feedback_photo_too_large'),
        ]);

        $bot = $this->bots->deliveryBot();

        if ($bot === null) {
            return back()->with('error', lng('error.feedback_not_configured'));
        }

        $sent = $this->bots->send(
            $bot,
            $this->buildText($request, $data),
            $request->file('photos') ?? [],
        );

        if (! $sent) {
            Log::error('Обращение из кабинета не доставлено', ['bot' => $bot]);
        }

        return $sent
            ? back()->with('success', lng('success.feedback_sent'))
            : back()->with('error', lng('error.feedback_sent'));
    }

    /**
     * Принимает апдейты Telegram и пересылает сообщения, написанные боту, владельцу.
     *
     * Endpoint: POST /telegram/webhook (без auth и CSRF, см. bootstrap/app.php).
     * Защита — секрет в заголовке X-Telegram-Bot-Api-Secret-Token.
     *
     * Настройка (выполняется вручную, один раз — доступа к проду нет):
     *   1. В .env задать TELEGRAM_BOT_USERNAME=@имя_бота и TELEGRAM_WEBHOOK_SECRET=<случайная_строка>
     *      (при пустом webhook_secret endpoint открыт для любого POST).
     *   2. Зарегистрировать вебхук боевым токеном и доменом:
     *      https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<домен>/telegram/webhook&secret_token=<SECRET>&allowed_updates=["message","edited_message"]
     *   3. Пользователь должен сам начать диалог с ботом (/start) — бот не может написать первым.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');

        if ($secret !== '' && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['ok' => false], 403);
        }

        $bot = $this->bots->deliveryBot();

        // Отвечаем 200, чтобы Telegram не зацикливал доставку при сбое конфигурации.
        if ($bot === null) {
            Log::warning('Telegram-вебхук: бот обратной связи не настроен, сообщение потеряно');

            return response()->json(['ok' => true]);
        }

        $message = $request->input('message') ?? $request->input('edited_message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        // Telegram повторяет апдейт, если ответ не успел уйти: без этой отметки
        // владелец получил бы одно и то же сообщение дважды.
        if (! $this->markHandled($request)) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->forwardTelegramMessage($bot, $message);
        } catch (Throwable $e) {
            // Повторную доставку не просим (иначе будут дубли), но сбой пишем в лог.
            Log::error('Пересылка сообщения из Telegram упала', [
                'error' => LogScrubber::scrub($e->getMessage()),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Принимает апдейты MAX и пересылает сообщения, написанные боту, владельцу.
     *
     * Endpoint: POST /max/webhook (без auth и CSRF, см. bootstrap/app.php).
     * Защита — секрет в заголовке X-Max-Bot-Api-Secret.
     *
     * Настройка (выполняется вручную, один раз — доступа к проду нет):
     *   1. В .env задать MAX_BOT_TOKEN, MAX_OWNER_ID (ID владельца в MAX) и
     *      MAX_WEBHOOK_SECRET=<случайная_строка>.
     *   2. Подписаться на события боевым токеном:
     *      POST https://platform-api2.max.ru/subscriptions
     *      Authorization: <MAX_BOT_TOKEN>
     *      {"url":"https://<домен>/max/webhook","update_types":["message_created"],"secret":"<SECRET>"}
     *   3. Владелец должен сам начать диалог с ботом, а его user_id — попасть в MAX_OWNER_ID.
     */
    public function maxWebhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.max.webhook_secret');

        if ($secret !== '' && $request->header('X-Max-Bot-Api-Secret') !== $secret) {
            return response()->json(['ok' => false], 403);
        }

        $bot = $this->bots->deliveryBot();

        if ($bot === null) {
            Log::warning('MAX-вебхук: бот обратной связи не настроен, сообщение потеряно');

            return response()->json(['ok' => true]);
        }

        if (! in_array($request->input('update_type'), ['message_created', 'message_edited'], true)) {
            return response()->json(['ok' => true]);
        }

        $message = $request->input('message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->forwardMaxMessage($bot, $message);
        } catch (Throwable $e) {
            // Повторную доставку не просим (иначе будут дубли), но сбой пишем в лог.
            Log::error('Пересылка сообщения из MAX упала', [
                'error' => LogScrubber::scrub($e->getMessage()),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Отмечает апдейт обработанным. false — он уже приходил (повтор доставки).
     */
    private function markHandled(Request $request): bool
    {
        $updateId = (int) $request->input('update_id');

        if ($updateId <= 0) {
            return true;
        }

        return Cache::add(
            CacheKeys::telegramUpdate('feedback', $updateId),
            true,
            now()->addMinutes(CacheKeys::TTL_TELEGRAM_UPDATE_MINUTES),
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function forwardTelegramMessage(string $bot, array $message): void
    {
        $chatId = $this->bots->telegramChatId();
        $senderChatId = (string) (is_array($message['chat'] ?? null) ? ($message['chat']['id'] ?? '') : '');

        // Сообщение из чата самого владельца пересылать не нужно.
        if ($senderChatId === $chatId) {
            return;
        }

        $from     = is_array($message['from'] ?? null) ? $message['from'] : [];
        $name     = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        $username = isset($from['username']) ? '@' . $from['username'] : '—';
        $text     = trim((string) ($message['text'] ?? $message['caption'] ?? ''));

        $lines = [
            '💬 Новое сообщение',
            '',
            '📍 Источник: Telegram',
            '',
            '👤 Имя: ' . ($name !== '' ? $name : '—'),
            '🔗 Username: ' . $username,
            '🆔 ID: ' . ($from['id'] ?? '—'),
            '',
            '📝 Сообщение:',
            $text !== '' ? $text : '—',
            '',
            '🕒 ' . now()->format('Y-m-d H:i'),
        ];

        $photos = $message['photo'] ?? null;
        $fileId = is_array($photos) && count($photos) > 0
            ? ($photos[array_key_last($photos)]['file_id'] ?? null)
            : null;

        $attachments = [];

        if (is_string($fileId) && $fileId !== '') {
            $url = $this->bots->telegramPhotoUrl($fileId);

            if ($url !== null) {
                $attachments[] = $url;
            }
        }

        $this->bots->send($bot, mb_substr(implode("\n", $lines), 0, 4000), $attachments);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function forwardMaxMessage(string $bot, array $message): void
    {
        $sender   = is_array($message['sender'] ?? null) ? $message['sender'] : [];
        $senderId = $sender['user_id'] ?? null;

        // Сообщение самого владельца пересылать не нужно.
        if ($senderId !== null && (string) $senderId === $this->bots->maxOwnerId()) {
            return;
        }

        $name     = trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? ''));
        $username = isset($sender['username']) && $sender['username'] !== null ? '@' . $sender['username'] : '—';
        $body     = is_array($message['body'] ?? null) ? $message['body'] : [];
        $text     = trim((string) ($body['text'] ?? ''));

        $lines = [
            '💬 Новое сообщение',
            '',
            '📍 Источник: MAX',
            '',
            '👤 Имя: ' . ($name !== '' ? $name : '—'),
            '🔗 Username: ' . $username,
            '🆔 ID: ' . ($senderId ?? '—'),
            '',
            '📝 Сообщение:',
            $text !== '' ? $text : '—',
            '',
            '🕒 ' . now()->format('Y-m-d H:i'),
        ];

        $attachments = [];

        foreach ($body['attachments'] ?? [] as $attachment) {
            $url = $attachment['payload']['url'] ?? null;

            if (($attachment['type'] ?? null) === 'image' && is_string($url) && $url !== '') {
                $attachments[] = $url;
            }
        }

        $this->bots->send($bot, mb_substr(implode("\n", $lines), 0, 4000), $attachments);
    }

    /**
     * @param  array{contact: string, message: string}  $data
     */
    private function buildText(Request $request, array $data): string
    {
        $user = $request->user();

        $name = $user?->name ?: trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));

        $lines = [
            '💬 Новое сообщение',
            '',
            '📍 Источник: сайт',
            '',
            '👤 Имя: ' . ($name !== '' ? $name : '—'),
            '📧 Email: ' . ($user?->email ?: '—'),
            '🆔 ID: ' . ($user?->id ?? '—'),
            '🔗 Страница: ' . ($request->header('referer') ?: '—'),
            '',
            '✉️ Контакт для ответа: ' . $data['contact'],
            '',
            '📝 Сообщение:',
            $data['message'],
        ];

        $photos = $request->file('photos') ?? [];
        if (count($photos) > 0) {
            $lines[] = '';
            $lines[] = '📎 Фото: ' . count($photos);
        }

        $lines[] = '';
        $lines[] = '🕒 ' . now()->format('Y-m-d H:i');

        return mb_substr(implode("\n", $lines), 0, 4000);
    }
}
