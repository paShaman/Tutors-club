<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\User;
use App\Support\UserCache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Сброс пользовательских кэшей после мутации данных.
     *
     * Версия данных инвалидирует дашборд, уроки, календарь, карточку ученика
     * и деревья тем; отдельно сбрасываются счётчики тарифа (usage).
     */
    protected function flushUserCache(int $userId): void
    {
        $user = Auth::user();

        if ($user === null || (int) $user->id !== $userId) {
            $user = User::find($userId);
        }

        if ($user !== null) {
            UserCache::flush($user);
        }
    }
}
