<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StudentTelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class TelegramController extends Controller
{
    public function __construct(private readonly StudentTelegramBotService $bot)
    {
    }

    /**
     * Выпускает новую ссылку привязки Telegram (прежняя перестаёт работать).
     */
    public function refresh(Request $request): RedirectResponse
    {
        if (! $this->bot->configured()) {
            return back()->with('error', lng('error.telegram_not_configured'));
        }

        $this->bot->invalidateLinkCode($request->user());

        return back()->with('success', lng('success.telegram_link'));
    }

    /**
     * Отвязывает Telegram от текущего аккаунта.
     */
    public function unlink(Request $request): RedirectResponse
    {
        $this->bot->unlink($request->user());

        return back()->with('success', lng('success.telegram_unlink'));
    }
}
