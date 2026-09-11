<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            'contact' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
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
        } catch (Throwable) {
            return back()->with('error', lng('error.feedback_sent'));
        }

        if (! $response->successful()) {
            return back()->with('error', lng('error.feedback_sent'));
        }

        return back()->with('success', lng('success.feedback_sent'));
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
            '',
            '🕒 ' . now()->format('Y-m-d H:i'),
        ];

        return mb_substr(implode("\n", $lines), 0, 4096);
    }
}
