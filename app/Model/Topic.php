<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Topic extends Model
{
    protected $fillable = [
        'user_id', 'subject_id', 'parent_id', 'name', 'position', 'is_deleted',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'parent_id'  => 'integer',
        'position'   => 'integer',
        'is_deleted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Деревья тем преподавателя по всем переданным предметам.
     *
     * @param array<int, string> $subjects
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function treesBySubject(int $userId, array $subjects): array
    {
        $trees = [];

        foreach ($subjects as $subject) {
            $trees[$subject] = self::treeForSubject($userId, $subject);
        }

        return $trees;
    }

    /**
     * Дерево тем преподавателя по предмету (тема → подтемы).
     * Предмет задаётся кодом из таблицы subjects.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function treeForSubject(int $userId, string $subject): array
    {
        $topics = self::query()
            ->where('user_id', $userId)
            ->whereHas('subject', fn ($query) => $query->where('code', $subject))
            ->where('is_deleted', 0)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $grouped = [];

        foreach ($topics as $topic) {
            $grouped[(int) ($topic->parent_id ?? 0)][] = $topic;
        }

        $build = function (int $parentId) use (&$build, $grouped): array {
            $nodes = [];

            foreach ($grouped[$parentId] ?? [] as $topic) {
                $nodes[] = [
                    'id'       => (int) $topic->id,
                    'name'     => $topic->name,
                    'position' => (int) $topic->position,
                    'children' => $build((int) $topic->id),
                ];
            }

            return $nodes;
        };

        return $build(0);
    }
}
