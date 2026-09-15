<?php

declare(strict_types=1);

namespace App\Model;

use App\Support\CacheKeys;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class PromoBanner extends Model
{
    public const DEFAULT_DISMISS_DAYS = 7;

    protected $fillable = [
        'title', 'message', 'button_text', 'button_url',
        'starts_at', 'ends_at', 'dismiss_days', 'is_active',
    ];

    protected $casts = [
        'starts_at'    => 'date',
        'ends_at'      => 'date',
        'dismiss_days' => 'integer',
        'is_active'    => 'boolean',
    ];

    /**
     * Промо-баннеры, которые нужно показывать прямо сейчас
     * (включены и попадают в окно показа).
     *
     * @return Collection<int, self>
     */
    public static function active(): Collection
    {
        $today = Carbon::now()->toDateString();

        return self::query()
            ->where('is_active', 1)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Данные активных баннеров для общего Inertia-пропса.
     *
     * Кэшируется до конца суток: окно показа баннера меняется по дате,
     * поэтому без TTL результат «залипнет» на границе периода.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activePayloads(): array
    {
        return Cache::remember(
            CacheKeys::promoBanners(),
            Carbon::now()->endOfDay(),
            fn (): array => self::active()
                ->map(fn (self $banner): array => $banner->toPayload())
                ->all(),
        );
    }

    /** Сброс кэша после правки баннеров в админке. */
    public static function flushCache(): void
    {
        Cache::forget(CacheKeys::promoBanners());
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'id'           => (int) $this->id,
            'title'        => $this->title,
            'message'      => $this->message,
            'button_text'  => $this->button_text,
            'button_url'   => $this->button_url,
            'dismiss_days' => (int) $this->dismiss_days,
            // updated_at служит токеном: после правки баннера скрытие сбрасывается.
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id'           => (int) $this->id,
            'title'        => $this->title,
            'message'      => $this->message,
            'button_text'  => $this->button_text,
            'button_url'   => $this->button_url,
            'starts_at'    => $this->starts_at?->toDateString(),
            'ends_at'      => $this->ends_at?->toDateString(),
            'dismiss_days' => (int) $this->dismiss_days,
            'is_active'    => (bool) $this->is_active,
        ];
    }
}
