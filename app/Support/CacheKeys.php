<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Единый реестр ключей кэша.
 *
 * Правила:
 *  - строку ключа не пишем в контроллерах/моделях, только через этот класс;
 *  - любой персональный ключ содержит id пользователя/ученика;
 *  - справочники сбрасываются явно (forget*), пользовательские данные —
 *    через версию (bumpData): старые ключи становятся недоступны и истекают по TTL.
 */
final class CacheKeys
{
    /** Сколько минут живёт кэш счётчиков тарифа. */
    public const TTL_USAGE_MINUTES = 1;

    /** Сколько минут живёт кэш плана тарифа (страховка от естественного истечения подписки). */
    public const TTL_TARIFF_MINUTES = 10;

    /** Сколько минут живут тяжёлые страницы. */
    public const TTL_PAGE_MINUTES = 10;

    // ─── Справочник предметов ───────────────────────────────────────────

    public static function subjectCodes(): string
    {
        return 'subjects.codes';
    }

    public static function subjectActive(): string
    {
        return 'subjects.active';
    }

    public static function subjectIdMap(): string
    {
        return 'subjects.id_map';
    }

    public static function subjectCodeMap(): string
    {
        return 'subjects.code_map';
    }

    public static function subjectSlugMap(): string
    {
        return 'subjects.slug_map';
    }

    public static function subjectNames(string $locale): string
    {
        return 'subjects.names.' . $locale;
    }

    /** Сброс всех ключей справочника предметов. */
    public static function forgetSubjects(): void
    {
        Cache::forget(self::subjectCodes());
        Cache::forget(self::subjectActive());
        Cache::forget(self::subjectIdMap());
        Cache::forget(self::subjectCodeMap());
        Cache::forget(self::subjectSlugMap());

        foreach (array_keys((array) config('locales.available', [])) as $locale) {
            Cache::forget(self::subjectNames((string) $locale));
        }
    }

    // ─── Промо-баннеры ──────────────────────────────────────────────────

    public static function promoBanners(): string
    {
        return 'promo.banners';
    }

    // ─── Переводы ───────────────────────────────────────────────────────

    /** Ключ включает подпись файла переводов, поэтому деплой сам обновляет кэш. */
    public static function langMessages(string $locale, string $signature): string
    {
        return 'lang.messages.' . $locale . '.' . $signature;
    }

    /** Админские переводы (admin.php) кэшируются отдельно от messages.php. */
    public static function langAdmin(string $locale, string $signature): string
    {
        return 'lang.admin.' . $locale . '.' . $signature;
    }

    // ─── Роли и тариф ───────────────────────────────────────────────────

    public static function userRoles(int $userId): string
    {
        return 'user.' . $userId . '.roles';
    }

    public static function tariffPlan(int $userId): string
    {
        return 'tariff.plan.' . $userId;
    }

    public static function tariffUsage(int $userId): string
    {
        return 'tariff.usage.' . $userId;
    }

    // ─── Версия пользовательских данных ─────────────────────────────────

    private static function dataVersionKey(int $userId): string
    {
        return 'data.version.' . $userId;
    }

    /**
     * Текущая версия данных пользователя.
     *
     * При промахе версия не просто возвращается нулём, а сразу записывается
     * меткой времени в миллисекундах: если ключ вытеснится из кэша (Redis
     * maxmemory), читатели не вернутся к старым записям «нулевой» версии,
     * которые ещё живут по своему TTL.
     */
    public static function dataVersion(int $userId): int
    {
        $key = self::dataVersionKey($userId);
        $value = Cache::get($key);

        if ($value !== null) {
            return (int) $value;
        }

        $version = (int) floor(microtime(true) * 1000);
        Cache::forever($key, $version);

        return $version;
    }

    /**
     * Инвалидирует все пользовательские ключи: старые версии истекают по TTL.
     *
     * Версия — метка времени в миллисекундах, а не счётчик с нуля: даже если
     * ключ версии когда-нибудь вытеснится из кэша, новая версия окажется
     * больше прежней и старые записи не «оживут».
     */
    public static function bumpData(int $userId): void
    {
        $key = self::dataVersionKey($userId);

        if (Cache::get($key) === null) {
            Cache::forever($key, (int) floor(microtime(true) * 1000));

            return;
        }

        Cache::increment($key);
    }

    // ─── Пользовательские данные ────────────────────────────────────────

    public static function dashboard(int $userId, int $version, string $locale): string
    {
        return 'dashboard.' . $userId . '.v' . $version . '.' . $locale;
    }

    public static function lessons(int $userId, int $version, ?int $studentId, string $subject, string $locale): string
    {
        return 'lessons.' . $userId . '.v' . $version . '.' . ($studentId ?? 0) . '.'
            . ($subject !== '' ? $subject : 'all') . '.' . $locale;
    }

    public static function calendarEvents(int $userId, int $version, string $start, string $end): string
    {
        return 'calendar.' . $userId . '.v' . $version . '.' . $start . '.' . $end;
    }

    /** Пропсы страницы календаря (ученики, предметы, деревья тем, статусы тем). */
    public static function calendarPage(int $userId, int $version, string $locale): string
    {
        return 'calendar.page.' . $userId . '.v' . $version . '.' . $locale;
    }

    /**
     * Карточка ученика. Содержит id владельца: ученика теоретически можно
     * «расшарить» между преподавателями, и тогда чужие темы в payload недопустимы.
     */
    public static function studentDetail(int $studentId, int $userId, int $version, string $locale): string
    {
        return 'student.' . $studentId . '.' . $userId . '.v' . $version . '.' . $locale;
    }

    public static function topicTree(int $userId, int $version, string $subject): string
    {
        return 'topics.tree.' . $userId . '.v' . $version . '.' . $subject;
    }

    // ─── Внешние сервисы ────────────────────────────────────────────────

    public static function telegramFile(string $fileId): string
    {
        return 'tg.file.' . md5($fileId);
    }
}
