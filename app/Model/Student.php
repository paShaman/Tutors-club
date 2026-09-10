<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name', 'description', 'is_deleted', 'class', 'type', 'avatar'
    ];

    protected $appends = ['current_class'];

    /**
     * Динамический расчёт текущего класса ученика.
     * Каждый сентябрь класс увеличивается на 1.
     * После 11 класса возвращает «окончил школу».
     */
    public function getCurrentClassAttribute(): string
    {
        $initialClass = (int) $this->class;

        if ($initialClass <= 0) {
            return '';
        }

        $created = \Carbon\Carbon::parse($this->created_at);
        $now = \Carbon\Carbon::now();

        // Учебный год создания: если месяц >= 9, то год начала = текущий год, иначе = предыдущий
        $creationAcademicYear = $created->month >= 9 ? $created->year : $created->year - 1;

        // Текущий учебный год
        $currentAcademicYear = $now->month >= 9 ? $now->year : $now->year - 1;

        $yearsPassed = $currentAcademicYear - $creationAcademicYear;

        $currentClass = $initialClass + $yearsPassed;

        if ($currentClass > 11) {
            return lng('ui.students.graduated');
        }

        return lng('ui.students.class_format', ['class' => $currentClass]);
    }

    /**
     * get students lessons
     */
    public function lessons()
    {
        return $this->hasMany('App\Model\Lesson');
    }

    /**
     * состояния тем у ученика
     */
    public function topicStates()
    {
        return $this->hasMany('App\Model\StudentTopic', 'student_id');
    }

    /**
     * журнал повторений тем у ученика
     */
    public function topicReviews()
    {
        return $this->hasMany('App\Model\TopicReview', 'student_id');
    }

    /**
     * добавление урока
     *
     * @param $params
     * @return bool
     */
    public function addLesson($params)
    {
        try {
            if (empty($params['subject']) || empty($params['price']) || empty($params['date'])) {
                throw new \Exception('empty_params');
            }

            $lesson = new Lesson([
                'subject'       => $params['subject'],
                'theme'         => $params['theme'] ?? '',
                'price'         => $params['price'],
                'duration'      => $params['duration'] ?? 0,
                'time'          => $params['time'] ?? 0,
                'date'          => $params['date'] ?? date('Y-m-d'),
                'date_payed'    => $params['date_payed'] ?? (!empty($params['is_payed']) ? date('Y-m-d') : null),
                'is_payed'      => $params['is_payed'] ?? (!empty($params['date_payed']) ? 1 : 0),
                'is_future'     => $params['is_future'] ?? (!empty($params['is_future']) ? 1 : 0),
            ]);

            $this->lessons()->save($lesson);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
