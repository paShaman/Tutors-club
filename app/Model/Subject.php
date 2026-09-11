<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class Subject extends Model
{
    protected $fillable = [
        'code', 'name', 'position', 'is_deleted',
    ];

    protected $casts = [
        'name'       => 'array',
        'position'   => 'integer',
        'is_deleted' => 'boolean',
    ];

    /**
     * Активные предметы в порядке отображения.
     *
     * @return Collection<int, self>
     */
    public static function active(): Collection
    {
        return self::query()
            ->where('is_deleted', 0)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Коды активных предметов — значения в lessons.subject / topics.subject.
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return self::active()->pluck('code')->all();
    }

    /**
     * Карта «код предмета => название на текущем языке».
     *
     * @return array<string, string>
     */
    public static function nameMap(): array
    {
        return self::active()
            ->mapWithKeys(fn (self $subject): array => [$subject->code => $subject->localizedName()])
            ->all();
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
}
