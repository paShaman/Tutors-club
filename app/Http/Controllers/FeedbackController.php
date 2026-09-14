<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Throwable;

final class FeedbackController extends Controller
{
    /**
     * Пересылает обращение из кабинета в Telegram владельца.
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

        $token  = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');

        if ($token === '' || $chatId === '') {
            return back()->with('error', lng('error.feedback_not_configured'));
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => $chatId,
                    'text'                     => $this->buildText($request, $data),
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful()) {
                return back()->with('error', lng('error.feedback_sent'));
            }

            foreach ($request->file('photos') ?? [] as $photo) {
                $sent = Http::timeout(30)
                    ->attach('photo', (string) file_get_contents($photo->getPathname()), $photo->getClientOriginalName())
                    ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                        'chat_id' => $chatId,
                    ]);

                if (! $sent->successful()) {
                    return back()->with('error', lng('error.feedback_sent'));
                }
            }
        } catch (Throwable) {
            return back()->with('error', lng('error.feedback_sent'));
        }

        return back()->with('success', lng('success.feedback_sent'));
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

        $token  = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');

        // Отвечаем 200, чтобы Telegram не зацикливал доставку при сбое конфигурации.
        if ($token === '' || $chatId === '') {
            return response()->json(['ok' => true]);
        }

        $message = $request->input('message') ?? $request->input('edited_message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->forwardToOwner($token, $chatId, $message);
        } catch (Throwable) {
            // Игнорируем: повторная доставка от Telegram создаст дубли.
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function forwardToOwner(string $token, string $chatId, array $message): void
    {
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
            '💬 Новое сообщение в боте',
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

        if ($fileId !== null) {
            Http::asJson()
                ->timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo'   => $fileId,
                    'caption' => mb_substr(implode("\n", $lines), 0, 1024),
                ]);

            return;
        }

        Http::asJson()
            ->timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => mb_substr(implode("\n", $lines), 0, 4096),
                'disable_web_page_preview' => true,
            ]);
    }

    /**
     * @param  array{contact: string, message: string}  $data
     */
    private function buildText(Request $request, array $data): string
    {
        $user = $request->user();

        $name = $user?->name ?: trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));

        $lines = [
            '💬 Новое сообщение из кабинета',
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

        return mb_substr(implode("\n", $lines), 0, 4096);
    }
}
