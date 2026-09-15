<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Lesson;
use App\Model\Topic;
use App\Model\User;
use App\Model\UserSubscription;
use App\Support\CacheKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class TariffService
{
    /** Функции с квотами — порядок используется при сборке payload. */
    public const FEATURES = ['students', 'lessons', 'topics'];

    /** Периоды подписки: месяц и год. */
    public const PERIODS = ['m', 'y'];

    /**
     * Активная подписка: не истёкшая, самая поздняя по id.
     */
    public function activeSubscription(User $user): ?UserSubscription
    {
        return $user->subscriptions()
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Последняя подписка (включая истёкшую) — для уведомления.
     */
    public function latestSubscription(User $user): ?UserSubscription
    {
        return $user->subscriptions()->orderByDesc('id')->first();
    }

    /**
     * Эффективный тариф: активная подписка либо free.
     */
    public function plan(User $user): string
    {
        return $this->planData($user)['plan'];
    }

    private function resolvePlan(?UserSubscription $subscription): string
    {
        $plan = $subscription?->plan ?: config('tariffs.default', 'free');

        if (!array_key_exists($plan, config('tariffs.plans', []))) {
            return config('tariffs.default', 'free');
        }

        return $plan;
    }

    public function isPaid(User $user): bool
    {
        return $this->plan($user) !== 'free';
    }

    /**
     * Платный тариф истёк и сейчас действует free.
     */
    public function expired(User $user): bool
    {
        return $this->planData($user)['expired'];
    }

    /**
     * Дата окончания активной подписки (для админской таблицы).
     */
    public function until(User $user): ?string
    {
        return $this->planData($user)['until'];
    }

    /**
     * Период активной подписки (для формы в админке).
     */
    public function period(User $user): ?string
    {
        return $this->planData($user)['period'];
    }

    /**
     * План, период и срок активной подписки.
     *
     * Кэш на TTL_TARIFF_MINUTES (страховка от естественного истечения подписки)
     * плюс явный сброс при назначении/снятии плана в админке.
     *
     * @return array{plan: string, period: string|null, until: string|null, started_at: string|null, expired: bool, expired_at: string|null}
     */
    private function planData(User $user): array
    {
        return Cache::remember(
            CacheKeys::tariffPlan((int) $user->id),
            now()->addMinutes(CacheKeys::TTL_TARIFF_MINUTES),
            function () use ($user): array {
                $active = $this->activeSubscription($user);
                $plan = $this->resolvePlan($active);
                $latest = $this->latestSubscription($user);

                $expired = $latest !== null
                    && $latest->plan !== 'free'
                    && $latest->expires_at !== null
                    && $latest->expires_at->isPast()
                    && $plan === 'free';

                return [
                    'plan'       => $plan,
                    'period'     => $active?->period,
                    'until'      => $active?->expires_at?->toDateString(),
                    'started_at' => $active?->starts_at?->toDateString(),
                    'expired'    => $expired,
                    'expired_at' => $expired ? $latest?->expires_at?->toDateString() : null,
                ];
            },
        );
    }

    /**
     * Лимит функции: null — без ограничений.
     */
    public function limit(User $user, string $feature): ?int
    {
        return $this->limitForPlan($this->plan($user), $feature);
    }

    private function limitForPlan(string $plan, string $feature): ?int
    {
        $limit = config('tariffs.plans.' . $plan . '.limits.' . $feature);

        return $limit === null ? null : (int) $limit;
    }

    /**
     * Использование квот одним блоком — счётчики ходят в БД на каждый запрос,
     * поэтому кэшируются на минуту и сбрасываются после мутаций.
     *
     * @return array<string, int>
     */
    private function usage(User $user): array
    {
        return Cache::remember(
            CacheKeys::tariffUsage((int) $user->id),
            now()->addMinutes(CacheKeys::TTL_USAGE_MINUTES),
            fn (): array => [
                'students' => $user->students()->count(),
                'lessons'  => Lesson::whereIn('student_id', $user->students()->select('students.id'))
                    ->where('is_deleted', 0)
                    ->count(),
                'topics'   => Topic::where('user_id', $user->id)
                    ->where('is_deleted', 0)
                    ->count(),
            ],
        );
    }

    /**
     * Текущее использование квоты.
     */
    public function used(User $user, string $feature): int
    {
        return $this->usage($user)[$feature] ?? 0;
    }

    public function canUse(User $user, string $feature, int $amount = 1): bool
    {
        $limit = $this->limit($user, $feature);

        return $limit === null || $this->used($user, $feature) + $amount <= $limit;
    }

    /**
     * Данные тарифа для фронтенда (общие Inertia-пропсы и настройки).
     *
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $data = $this->planData($user);
        $usage = $this->usage($user);

        $limits = [];
        $can = [];

        foreach (self::FEATURES as $feature) {
            $limit = $this->limitForPlan($data['plan'], $feature);

            $limits[$feature] = $limit;
            $can[$feature] = $limit === null || $usage[$feature] + 1 <= $limit;
        }

        return [
            'plan'        => $data['plan'],
            'is_paid'     => $data['plan'] !== 'free',
            'period'      => $data['period'],
            'until'       => $data['until'],
            'started_at'  => $data['started_at'],
            'expired'     => $data['expired'],
            'expired_at'  => $data['expired_at'],
            'limits'      => $limits,
            'usage'       => $usage,
            'can'         => $can,
            'price_month' => (int) config('tariffs.plans.paid.price_month', 0),
            'price_year'  => (int) config('tariffs.plans.paid.price_year', 0),
        ];
    }

    /**
     * Назначение подписки администратором.
     *
     * @param string      $plan      код плана из config/tariffs.php
     * @param string      $period    период: m (месяц) или y (год)
     * @param string|null $expiresAt явная дата окончания; пусто — считается от периода
     */
    public function assign(User $user, string $plan, string $period, ?string $expiresAt = null): bool
    {
        if (!array_key_exists($plan, (array) config('tariffs.plans', [])) || !in_array($period, self::PERIODS, true)) {
            return false;
        }

        $startsAt = Carbon::now();
        $expires = $expiresAt !== null && trim($expiresAt) !== ''
            ? Carbon::parse($expiresAt)->endOfDay()
            : ($period === 'y' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth());

        if ($expires->isPast()) {
            return false;
        }

        $subscription = new UserSubscription([
            'user_id'    => $user->id,
            'plan'       => $plan,
            'period'     => $period,
            'starts_at'  => $startsAt,
            'expires_at' => $expires,
        ]);

        try {
            if (!$subscription->save()) {
                return false;
            }
        } catch (Throwable) {
            // Например, план отсутствует в ENUM колонки — в UI уйдёт понятная ошибка.
            return false;
        }

        $this->forget($user);

        return true;
    }

    /**
     * Снятие активной подписки: пользователь возвращается на бесплатный план.
     */
    public function cancel(User $user): bool
    {
        $subscription = $this->activeSubscription($user);

        if ($subscription === null) {
            return false;
        }

        $subscription->expires_at = Carbon::now();

        if (!$subscription->save()) {
            return false;
        }

        $this->forget($user);

        return true;
    }

    /**
     * Полный сброс кэша тарифа пользователя (план + счётчики).
     */
    public function forget(User $user): void
    {
        Cache::forget(CacheKeys::tariffPlan((int) $user->id));
        Cache::forget(CacheKeys::tariffUsage((int) $user->id));
    }

    /**
     * Сброс счётчиков после создания/удаления ученика, урока или темы.
     */
    public function forgetUsage(User $user): void
    {
        Cache::forget(CacheKeys::tariffUsage((int) $user->id));
    }
}
