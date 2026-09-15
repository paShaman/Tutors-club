<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StudentTelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

final class StudentBotController extends Controller
{
    public function __construct(private readonly StudentTelegramBotService $bot)
    {
    }

    /**
     * Принимает апдейты Telegram-бота управления учениками.
     *
     * Установка вебхука (выполняется вручную, однажды):
     *   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<домен>/telegram/students/webhook&secret_token=<SECRET>&allowed_updates=["message","callback_query"]
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.telegram_students.webhook_secret');

        if ($secret !== '' && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['ok' => false], 403);
        }

        // Отвечаем 200, чтобы Telegram не зацикливал доставку при сбое конфигурации.
        if (! $this->bot->configured()) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->bot->handleUpdate($request->all());
        } catch (Throwable) {
            // Игнорируем: повторная доставка от Telegram создаст дубли.
        }

        return response()->json(['ok' => true]);
    }
}
