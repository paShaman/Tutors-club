<?php

declare(strict_types=1);

namespace App\Model;

use App\Support\CacheKeys;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

final class Subject extends Model
{
    protected $fillable = [
        'code', 'slug', 'name', 'position', 'is_deleted',
    ];

    protected $casts = [
        'name'       => 'array',
        'position'   => 'integer',
        'is_deleted' => 'boolean',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'subject_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'subject_id');
    }

    /**
     * Активные предметы в порядке отображения.
     *
     * Справочник меняется только администратором, поэтому кэшируется до явного сброса.
     *
     * @return Collection<int, self>
     */
    public static function active(): Collection
    {
        return Cache::rememberForever(
            CacheKeys::subjectActive(),
            fn (): Collection => self::query()
                ->where('is_deleted', 0)
                ->orderBy('position')
                ->orderBy('id')
                ->get(),
        );
    }

    /**
     * Коды активных предметов (topics.subject_id / lessons.subject_id ссылаются на subjects.id).
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return Cache::rememberForever(
            CacheKeys::subjectCodes(),
            fn (): array => self::active()->pluck('code')->all(),
        );
    }

    /**
     * Карта «код предмета => id» по всем предметам (для разбора входящих кодов).
     *
     * @return array<string, int>
     */
    public static function idMap(): array
    {
        return Cache::rememberForever(
            CacheKeys::subjectIdMap(),
            fn (): array => self::query()
                ->pluck('id', 'code')
                ->map(fn ($id): int => (int) $id)
                ->all(),
        );
    }

    /**
     * Карта «id предмета => код» по всем предметам (для отдачи кода в UI).
     *
     * @return array<int, string>
     */
    public static function codeMap(): array
    {
        return Cache::rememberForever(
            CacheKeys::subjectCodeMap(),
            fn (): array => self::query()->pluck('code', 'id')->all(),
        );
    }

    /**
     * Карта «код предмета => slug» для построения красивых URL.
     *
     * @return array<string, string>
     */
    public static function slugMap(): array
    {
        return Cache::rememberForever(
            CacheKeys::subjectSlugMap(),
            fn (): array => self::query()->pluck('slug', 'code')->all(),
        );
    }

    /**
     * Карта «код предмета => название на текущем языке».
     *
     * @return array<string, string>
     */
    public static function nameMap(): array
    {
        $locale = (string) app()->getLocale();

        return Cache::rememberForever(
            CacheKeys::subjectNames($locale),
            fn (): array => self::active()
                ->mapWithKeys(fn (self $subject): array => [$subject->code => $subject->localizedName()])
                ->all(),
        );
    }

    /** Сброс кэша справочника после правки предметов в админке. */
    public static function flushCache(): void
    {
        CacheKeys::forgetSubjects();
    }

    /**
     * Название предмета на текущем языке с запасными вариантами.
     */
    public function localizedName(): string
    {
        $names = is_array($this->name) ? $this->name : [];

        $locale = (string) app()->getLocale();

        if (!empty($names[$locale])) {
            return (string) $names[$locale];
        }

        $fallback = (string) config('app.fallback_locale', 'ru');

        if (!empty($names[$fallback])) {
            return (string) $names[$fallback];
        }

        foreach ($names as $name) {
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return (string) $this->code;
    }

    /**
     * Данные предмета для админской панели.
     *
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id'            => (int) $this->id,
            'code'          => (string) $this->code,
            'slug'          => (string) $this->slug,
            'name'          => is_array($this->name) ? $this->name : [],
            'position'      => (int) $this->position,
            'is_deleted'    => (bool) $this->is_deleted,
            'lessons_count' => (int) ($this->lessons_count ?? 0),
            'topics_count'  => (int) ($this->topics_count ?? 0),
        ];
    }
}
