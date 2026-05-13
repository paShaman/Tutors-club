<?php

namespace App\Model;

use App\Notification;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name', 'description', 'is_deleted', 'class', 'type'
    ];

    /**
     * get students lessons
     */
    public function lessons()
    {
        return $this->hasMany('App\Model\Lesson');
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
            Notification::put($e->getMessage());
            return false;
        }
    }
}
