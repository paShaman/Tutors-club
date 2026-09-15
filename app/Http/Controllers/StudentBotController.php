<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StudentTelegramBotService;
use App\Support\CacheKeys;
use App\Support\LogScrubber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
            Log::warning('Telegram-бот учеников не настроен (токен или username), апдейт пропущен');

            return response()->json(['ok' => true]);
        }

        // Telegram повторяет апдейт, если ответ не успел уйти: обрабатываем каждый
        // update_id ровно один раз, иначе повторная доставка создаст дубликаты.
        if (! $this->markHandled($request)) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->bot->handleUpdate($request->all());
        } catch (Throwable $e) {
            // Повторную доставку не просим, но сбой обязан попасть в лог:
            // иначе «бот молчит» невозможно отличить от сетевой задержки.
            // Текст прогоняем через LogScrubber: в URL Telegram есть токен.
            Log::error('Обработка апдейта Telegram-бота учеников упала', [
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
            CacheKeys::telegramUpdate('students', $updateId),
            true,
            now()->addMinutes(CacheKeys::TTL_TELEGRAM_UPDATE_MINUTES),
        );
    }
}
