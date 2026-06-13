<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class LessonController extends Controller
{
    /**
     * Lessons list page.
     */
    public function getLessons(): Response
    {
        $students = Auth::user()->students()->get()->keyBy('id')->toArray();

        $selectedStudentId = request()->query('student_id');

        $lessonsQuery = Lesson::where('is_deleted', 0)
            ->whereIn('student_id', array_keys($students));

        if ($selectedStudentId && isset($students[(int) $selectedStudentId])) {
            $lessonsQuery->where('student_id', (int) $selectedStudentId);
        }

        $lessons = $lessonsQuery->orderBy('date', 'desc')->get()->toArray();

        $sortedLessons = [];

        $flYearOpen = true;
        $flMonthOpen = true;

        foreach ($lessons as $lesson) {
            $dt = \Carbon\Carbon::parse($lesson['date']);

            if (empty($sortedLessons[$dt->year])) {
                $sortedLessons[$dt->year]['open'] = $flYearOpen;

                $flYearOpen = false;
            }

            if (empty($sortedLessons[$dt->year]['months'][$dt->month])) {
                $sortedLessons[$dt->year]['months'][$dt->month]['open'] = $flMonthOpen;

                $flMonthOpen = false;
            }

            if (empty($sortedLessons[$dt->year]['months'][$dt->month]['students'][$lesson['student_id']])) {
                $sortedLessons[$dt->year]['months'][$dt->month]['students'][$lesson['student_id']] = [
                    'student' => $students[$lesson['student_id']],
                    'lessons' => []
                ];
            }

            $sortedLessons[$dt->year]['months'][$dt->month]['students'][$lesson['student_id']]['lessons'][] = $lesson;
        }

        // Sums
        foreach ($sortedLessons as &$year) {
            foreach ($year['months'] as &$month) {
                foreach ($month['students'] as &$student) {
                    $student['sum'] = 0;
                    $student['sum_not_payed'] = 0;
                    $student['sum_special'] = 0;
                    $student['cnt'] = 0;
                    $student['cnt_not_payed'] = 0;
                    $student['cnt_special'] = 0;
                    $student['cnt_all'] = 0;

                    foreach ($student['lessons'] as $lesson) {
                        if ($lesson['is_future']) {
                            continue;
                        }
                        if (!empty($student['student']['type'])) {
                            $student['sum_special'] += $lesson['price'];
                            $student['cnt_special']++;
                        } else {
                            $student['cnt_all']++;
                            if ($lesson['is_payed']) {
                                $student['sum'] += $lesson['price'];
                                $student['cnt']++;
                            } else {
                                $student['sum_not_payed'] += $lesson['price'];
                                $student['cnt_not_payed']++;
                            }
                        }
                    }
                }

                $month['sum'] = array_sum(array_column($month['students'], 'sum'));
                $month['sum_not_payed'] = array_sum(array_column($month['students'], 'sum_not_payed'));
                $month['sum_special'] = array_sum(array_column($month['students'], 'sum_special'));
                $month['cnt'] = array_sum(array_column($month['students'], 'cnt'));
                $month['cnt_not_payed'] = array_sum(array_column($month['students'], 'cnt_not_payed'));
                $month['cnt_special'] = array_sum(array_column($month['students'], 'cnt_special'));
                $month['cnt_all'] = array_sum(array_column($month['students'], 'cnt_all'));
            }

            $year['sum'] = array_sum(array_column($year['months'], 'sum'));
            $year['sum_not_payed'] = array_sum(array_column($year['months'], 'sum_not_payed'));
            $year['sum_special'] = array_sum(array_column($year['months'], 'sum_special'));
            $year['cnt'] = array_sum(array_column($year['months'], 'cnt'));
            $year['cnt_not_payed'] = array_sum(array_column($year['months'], 'cnt_not_payed'));
            $year['cnt_special'] = array_sum(array_column($year['months'], 'cnt_special'));
            $year['cnt_all'] = array_sum(array_column($year['months'], 'cnt_all'));
        }

        // Remove deleted students from the student list
        $activeStudents = [];
        foreach ($students as $key => $item) {
            if (empty($item['is_deleted'])) {
                $activeStudents[] = $item;
            }
        }
        usort($activeStudents, function ($a, $b) {
            $aType = empty($a['type']) ? 0 : 1;
            $bType = empty($b['type']) ? 0 : 1;

            if ($aType !== $bType) {
                return $aType - $bType;
            }

            if ($a['name'] == $b['name']) {
                return 0;
            }

            return $a['name'] < $b['name'] ? -1 : 1;
        });

        return Inertia::render('Lessons', [
            'sortedLessons'     => $sortedLessons,
            'students'          => $activeStudents,
            'selectedStudentId' => $selectedStudentId ? (int) $selectedStudentId : null,
            'lessonsSubjects'   => Lesson::LESSON_SUBJECTS,
            'defaultPrice'      => Lesson::PRICE_DEFAULT,
            'defaultDuration'   => Lesson::DURATION_DEFAULT,
            'defaultDate'       => date('Y-m-d'),
        ]);
    }

    /**
     * Add or edit a lesson.
     */
    public function editLesson(): JsonResponse
    {
        $rules = [
            'lesson_student_id' => 'required',
            'lesson_subject'    => 'required',
            'lesson_date'       => 'required',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return ControllerHelper::resultError($validator);
        }

        $str = 'add_lesson';

        $params = [
            'subject'       => $post['lesson_subject'] ?? null,
            'theme'         => $post['lesson_theme'] ?? null,
            'price'         => $post['lesson_price'] ?? 0,
            'duration'      => $post['lesson_duration'] ?? null,
            'date'          => $post['lesson_date'] ?? null,
            'time'          => $post['lesson_time'] ?? null,
            'date_payed'    => $post['lesson_date_payed'] ?? null,
            'is_payed'      => isset($post['lesson_is_payed']) && $post['lesson_is_payed'] == 'on' ? 1 : 0,
            'is_future'     => isset($post['lesson_is_future']) && $post['lesson_is_future'] == 'on' ? 1 : 0,
        ];

        if (!empty($post['lesson_id'])) {
            $str = 'edit_lesson';
            $lessonId = $post['lesson_id'];

            $result = (new Lesson())->editLesson($lessonId, $params);
        } else {
            $student = Student::findOrFail($post['lesson_student_id']);

            $result = $student->addLesson($params);
        }

        if (empty($result)) {
            return ControllerHelper::resultError(lng('error.' . $str));
        }

        return ControllerHelper::resultSuccess(lng('success.' . $str));
    }

    /**
     * Delete a lesson.
     */
    public function deleteLesson(): JsonResponse
    {
        $rules = [
            'lesson_id' => 'required|integer',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return ControllerHelper::resultError($validator);
        }

        $result = (new Lesson())->deleteLesson($post['lesson_id']);

        if (empty($result)) {
            return ControllerHelper::resultError(lng('error.del_lesson'));
        }

        return ControllerHelper::resultSuccess(lng('success.del_lesson'));
    }

    /**
     * Toggle lesson payment status.
     */
    public function payLesson(): JsonResponse
    {
        $rules = [
            'lesson_id' => 'required|integer',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return ControllerHelper::resultError($validator);
        }

        $result = (new Lesson())->payLesson($post['lesson_id']);

        if (empty($result)) {
            return ControllerHelper::resultError(lng('error.del_lesson'));
        }

        $lesson = Lesson::find($post['lesson_id']);

        return ControllerHelper::resultSuccess([
            'is_payed'   => $lesson->is_payed,
            'date_payed' => \Carbon\Carbon::parse($lesson->date_payed)->format('d.m.Y'),
        ]);
    }
}