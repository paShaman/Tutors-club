<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TopicReview extends Model
{
    protected $fillable = [
        'student_id', 'topic_id', 'reviewed_on', 'comment',
    ];

    protected $casts = [
        'student_id'  => 'integer',
        'topic_id'    => 'integer',
        'reviewed_on' => 'date',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
