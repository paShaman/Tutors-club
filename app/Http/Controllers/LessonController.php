<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\Student;
use App\Model\StudentTopic;
use App\Model\Topic;
use App\Model\TopicReview;
use Illuminate\Http\RedirectResponse;
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

        // Названия выбранных тем/подтем, чтобы показывать их в списке без N+1.
        $topicNames = Topic::where('user_id', Auth::id())
            ->where('is_deleted', 0)
            ->pluck('name', 'id');

        foreach ($lessons as &$lesson) {
            $lesson['date'] = \Carbon\Carbon::parse($lesson['date'])->format('Y-m-d');
            $lesson['date_payed'] = $lesson['date_payed']
                ? \Carbon\Carbon::parse($lesson['date_payed'])->format('Y-m-d H:i')
                : null;
            $lesson['topic_name'] = $lesson['topic_id'] ? ($topicNames[$lesson['topic_id']] ?? null) : null;
            $lesson['subtopic_name'] = $lesson['subtopic_id'] ? ($topicNames[$lesson['subtopic_id']] ?? null) : null;
        }
        unset($lesson);

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
            'topicTree'         => Topic::treesBySubject((int) Auth::id(), Lesson::LESSON_SUBJECTS),
            'topicStatuses'     => StudentTopic::statusMapForStudents(array_keys($students)),
            'defaultPrice'      => config('lesson.default_price'),
            'defaultDuration'   => config('lesson.default_duration'),
            'defaultDate'       => date('Y-m-d'),
        ]);
    }

    /**
     * Add or edit a lesson.
     */
    public function editLesson(): RedirectResponse
    {
        $rules = [
            'lesson_student_id'  => 'required',
            'lesson_subject'     => 'required',
            'lesson_date'        => 'required',
            'lesson_topic_id'     => 'nullable|integer',
            'lesson_subtopic_id'  => 'nullable|integer',
            'lesson_topic_status' => 'nullable|in:in_progress,mastered,review',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subject = (string) ($post['lesson_subject'] ?? '');

        [$topicId, $subtopicId, $topicError] = $this->resolveLessonTopics($post, $subject);

        if ($topicError) {
            return back()->with('error', lng($topicError))->withInput();
        }

        $str = 'add_lesson';

        $params = [
            'subject'       => $subject,
            'topic_id'      => $topicId,
            'subtopic_id'   => $subtopicId,
            'comment'       => $post['lesson_comment'] ?? null,
            'price'         => $post['lesson_price'] ?? 0,
            'duration'      => $post['lesson_duration'] ?? null,
            'date'          => $post['lesson_date'] ?? null,
            'time'          => $post['lesson_time'] ?? null,
            'date_payed'    => $post['lesson_date_payed'] ?? null,
            'is_payed'      => !empty($post['lesson_is_payed']) ? 1 : 0,
            'is_future'     => !empty($post['lesson_is_future']) ? 1 : 0,
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
            return back()->with('error', lng('error.' . $str));
        }

        $statusTopicId = $subtopicId ?? $topicId;

        if ($statusTopicId !== null) {
            $this->applyTopicStatus(
                (int) $post['lesson_student_id'],
                (int) $statusTopicId,
                (string) ($post['lesson_topic_status'] ?? 'in_progress'),
                (string) ($post['lesson_date'] ?? date('Y-m-d')),
                !empty($post['lesson_comment']) ? (string) $post['lesson_comment'] : null,
            );
        }

        return back()->with('success', lng('success.' . $str));
    }

    /**
     * Применяет статус темы к ученику по итогам урока.
     */
    private function applyTopicStatus(int $studentId, int $topicId, string $status, string $date, ?string $comment): void
    {
        $studentTopic = StudentTopic::firstOrNew([
            'student_id' => $studentId,
            'topic_id'   => $topicId,
        ]);

        if ($status === 'review') {
            TopicReview::firstOrCreate(
                [
                    'student_id'  => $studentId,
                    'topic_id'    => $topicId,
                    'reviewed_on' => $date,
                ],
                ['comment' => $comment],
            );

            if (!$studentTopic->exists || $studentTopic->status === StudentTopic::STATUS_NOT_STARTED) {
                $studentTopic->status = StudentTopic::STATUS_IN_PROGRESS;
            }

            $current = $studentTopic->last_reviewed_at
                ? $studentTopic->last_reviewed_at->toDateString()
                : null;

            if ($current === null || $date > $current) {
                $studentTopic->last_reviewed_at = $date;
            }

            $studentTopic->save();

            return;
        }

        if ($status === StudentTopic::STATUS_MASTERED) {
            $studentTopic->status = StudentTopic::STATUS_MASTERED;

            if (!$studentTopic->mastered_at || $date > $studentTopic->mastered_at->toDateString()) {
                $studentTopic->mastered_at = $date;
            }

            $studentTopic->save();

            return;
        }

        $studentTopic->status = StudentTopic::STATUS_IN_PROGRESS;
        $studentTopic->save();
    }

    /**
     * Проверяет, что тема/подтема принадлежат пользователю и предмету урока.
     *
     * @param array<string, mixed> $post
     * @return array{0: int|null, 1: int|null, 2: string|null}
     */
    private function resolveLessonTopics(array $post, string $subject): array
    {
        $userId = (int) Auth::id();

        $topicId = !empty($post['lesson_topic_id']) ? (int) $post['lesson_topic_id'] : null;
        $subtopicId = !empty($post['lesson_subtopic_id']) ? (int) $post['lesson_subtopic_id'] : null;

        $topic = null;

        if ($topicId !== null) {
            $topic = Topic::where('user_id', $userId)
                ->where('is_deleted', 0)
                ->whereNull('parent_id')
                ->where('subject', $subject)
                ->find($topicId);

            if (!$topic) {
                return [null, null, 'error.add_lesson'];
            }
        }

        if ($subtopicId !== null) {
            $subtopic = Topic::where('user_id', $userId)
                ->where('is_deleted', 0)
                ->where('subject', $subject)
                ->whereNotNull('parent_id')
                ->find($subtopicId);

            if (!$subtopic) {
                return [null, null, 'error.add_lesson'];
            }

            // Подтема должна относиться к выбранной теме.
            if ($topic !== null && (int) $subtopic->parent_id !== (int) $topic->id) {
                return [null, null, 'error.add_lesson'];
            }

            // Если выбрана только подтема — темой считаем её родителя.
            if ($topic === null) {
                $topic = Topic::where('user_id', $userId)
                    ->where('is_deleted', 0)
                    ->find((int) $subtopic->parent_id);

                if (!$topic) {
                    return [null, null, 'error.add_lesson'];
                }
            }
        }

        return [
            $topic !== null ? (int) $topic->id : null,
            $subtopicId,
            null,
        ];
    }

    /**
     * Delete a lesson.
     */
    public function deleteLesson(): RedirectResponse
    {
        $rules = [
            'lesson_id' => 'required|integer',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = (new Lesson())->deleteLesson($post['lesson_id']);

        if (empty($result)) {
            return back()->with('error', lng('error.del_lesson'));
        }

        return back()->with('success', lng('success.del_lesson'));
    }

    /**
     * Toggle lesson payment status.
     */
    public function payLesson(): RedirectResponse
    {
        $rules = [
            'lesson_id' => 'required|integer',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = (new Lesson())->payLesson($post['lesson_id']);

        if (empty($result)) {
            return back()->with('error', lng('error.del_lesson'));
        }

        return back();
    }
}