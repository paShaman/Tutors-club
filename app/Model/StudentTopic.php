<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudentTopic extends Model
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_MASTERED    = 'mastered';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_MASTERED,
    ];

    protected $fillable = [
        'student_id', 'topic_id', 'status', 'mastered_at', 'last_reviewed_at',
    ];

    protected $casts = [
        'student_id'       => 'integer',
        'topic_id'         => 'integer',
        'mastered_at'      => 'date',
        'last_reviewed_at' => 'date',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
