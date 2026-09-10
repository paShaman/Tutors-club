<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Lesson;
use App\Model\Topic;
use App\Model\User;
use App\Model\UserSubscription;

final class TariffService
{
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
        return $this->resolvePlan($this->activeSubscription($user));
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
        $latest = $this->latestSubscription($user);

        return $latest !== null
            && $latest->plan !== 'free'
            && $latest->expires_at !== null
            && $latest->expires_at->isPast()
            && $this->plan($user) === 'free';
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
     * Текущее использование квоты.
     */
    public function used(User $user, string $feature): int
    {
        return match ($feature) {
            'students' => $user->students()->count(),
            'lessons'  => Lesson::whereIn('student_id', $user->students()->select('students.id'))
                ->where('is_deleted', 0)
                ->count(),
            'topics'   => Topic::where('user_id', $user->id)
                ->where('is_deleted', 0)
                ->count(),
            default    => 0,
        };
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
        $subscription = $this->activeSubscription($user);
        $plan = $this->resolvePlan($subscription);
        $latest = $this->latestSubscription($user);

        $limits = [];
        $usage = [];
        $can = [];

        foreach (['students', 'lessons', 'topics'] as $feature) {
            $limit = $this->limitForPlan($plan, $feature);
            $used = $this->used($user, $feature);

            $limits[$feature] = $limit;
            $usage[$feature] = $used;
            $can[$feature] = $limit === null || $used + 1 <= $limit;
        }

        $expired = $latest !== null
            && $latest->plan !== 'free'
            && $latest->expires_at !== null
            && $latest->expires_at->isPast()
            && $plan === 'free';

        return [
            'plan'        => $plan,
            'is_paid'     => $plan !== 'free',
            'period'      => $subscription?->period,
            'until'       => $subscription?->expires_at?->toDateString(),
            'started_at'  => $subscription?->starts_at?->toDateString(),
            'expired'     => $expired,
            'expired_at'  => $expired ? $latest?->expires_at?->toDateString() : null,
            'limits'      => $limits,
            'usage'       => $usage,
            'can'         => $can,
            'price_month' => (int) config('tariffs.plans.paid.price_month', 0),
            'price_year'  => (int) config('tariffs.plans.paid.price_year', 0),
        ];
    }
}
