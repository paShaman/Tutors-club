<?php

declare(strict_types=1);

namespace App\Support;

use App\Model\User;
use App\Services\TariffService;

/**
 * Инвалидация пользовательских кэшей после мутации данных.
 *
 * Единая точка входа и для web-контроллеров, и для Telegram-бота: обе части
 * пишут в одни и те же таблицы, поэтому обязаны гасить одни и те же ключи.
 */
final class UserCache
{
    public static function flush(User $user): void
    {
        CacheKeys::bumpData((int) $user->id);

        app(TariffService::class)->forgetUsage($user);
    }
}
