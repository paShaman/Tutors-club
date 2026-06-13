<?php

namespace App\Model;

use App\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    const LESSON_SUBJECTS = [
        'lesson_subject_maths',
        'lesson_subject_informatics',
        'lesson_subject_english',
    ];

    protected $fillable = [
        'student_id', 'subject', 'theme', 'price', 'duration', 'is_payed', 'date', 'date_payed', 'time', 'is_deleted', 'is_future'
    ];

    /**
     * удаление урока
     *
     * @param $lessonId
     * @return bool
     */
    public function deleteLesson($lessonId) : bool
    {
        try {
            if (empty($lessonId)) {
                throw new \Exception('empty_params');
            }

            $lesson = self::findOrFail($lessonId);

            if (!$lesson) {
                throw new \Exception('lesson_not_found');
            }

            $lesson->is_deleted = 1;
            $lesson->save();

            return true;
        } catch (\Throwable $e) {
            Notification::put($e->getMessage());
            return false;
        }
    }

    /**
     * удаление урока
     *
     * @param $lessonId
     * @return bool
     */
    public function payLesson($lessonId) : bool
    {
        try {
            if (empty($lessonId)) {
                throw new \Exception('empty_params');
            }

            $lesson = self::findOrFail($lessonId);

            if (!$lesson) {
                throw new \Exception('lesson_not_found');
            }

            $lesson->is_payed = !$lesson->is_payed;
            $lesson->date_payed = $lesson->is_payed ? Carbon::now() : null;
            $lesson->save();

            return true;
        } catch (\Throwable $e) {
            Notification::put($e->getMessage());
            return false;
        }
    }

    /**
     * изменение урока
     *
     * @param $lessonId
     * @param $params
     * @return bool
     */
    public function editLesson($lessonId, $params) : bool
    {
        try {
            if (empty($lessonId) || empty($params['subject']) || /*empty($params['price']) ||*/ empty($params['date'])) {
                throw new \Exception('empty_params');
            }

            $lesson = self::findOrFail($lessonId);

            if (!$lesson) {
                throw new \Exception('lesson_not_found');
            }

            $lesson->subject    = $params['subject'];
            $lesson->theme      = $params['theme'] ?? '';
            $lesson->price      = $params['price'];
            $lesson->duration   = $params['duration'] ?? 0;
            $lesson->time       = $params['time'] ?? 0;
            $lesson->date       = $params['date'] ?? date('Y-m-d');
            $lesson->date_payed = $params['date_payed'] ?? (!empty($lesson['is_payed']) ? date('Y-m-d') : null);
            $lesson->is_payed   = $params['is_payed'] ?? (!empty($lesson['date_payed']) ? 1 : 0);
            $lesson->is_future  = $params['is_future'] ?? 0;
            $lesson->save();

            return true;
        } catch (\Throwable $e) {
            Notification::put($e->getMessage());
            return false;
        }
    }
}
