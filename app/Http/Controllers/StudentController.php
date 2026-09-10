<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\Student;
use App\Model\StudentTopic;
use App\Model\Topic;
use App\Model\TopicReview;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class StudentController extends Controller
{
    /**
     * Students list page.
     */
    public function getStudents(): Response
    {
        $students = Auth::user()->students()
            ->orderBy('is_deleted')
            ->orderBy('type')
            ->get();

        $deletedFlag = false;
        $specialFlag = false;

        foreach ($students as $student) {
            if (!empty($student->is_deleted)) {
                $deletedFlag = true;
            }
            if (!empty($student->type)) {
                $specialFlag = true;
            }
        }

        return Inertia::render('Students', [
            'students'        => $students->toArray(),
            'deletedFlag'     => $deletedFlag,
            'specialFlag'     => $specialFlag,
        ]);
    }

    /**
     * Student detail page with statistics.
     */
    public function show(Student $student): Response
    {
        $user = Auth::user();

        $isOwner = $user->students()->where('students.id', $student->id)->exists();

        if (!$isOwner) {
            abort(404);
        }

        $lessons = Lesson::where('student_id', $student->id)
            ->where('is_deleted', 0)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        $totalLessons = 0;
        $paidLessons = 0;
        $earned = 0;
        $debt = 0;
        $totalMinutes = 0;
        $firstLessonDate = null;
        $lastLessonDate = null;

        foreach ($lessons as $lesson) {
            if (!empty($lesson->is_future)) {
                continue;
            }

            $date = $lesson->date ? Carbon::parse($lesson->date)->toDateString() : null;

            $totalLessons++;
            $totalMinutes += (int) $lesson->duration > 0 ? (int) $lesson->duration : 60;

            if (!empty($lesson->is_payed)) {
                $paidLessons++;
                $earned += (int) $lesson->price;
            } else {
                $debt += (int) $lesson->price;
            }

            if ($date !== null) {
                if ($firstLessonDate === null || $date < $firstLessonDate) {
                    $firstLessonDate = $date;
                }

                if ($lastLessonDate === null || $date > $lastLessonDate) {
                    $lastLessonDate = $date;
                }
            }
        }

        $sortedLessons = [];

        $topicNames = Topic::where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->pluck('name', 'id');

        foreach ($lessons as $lesson) {
            $date = Carbon::parse($lesson->date);
            $year = (int) $date->year;
            $month = (int) $date->month;

            if (empty($sortedLessons[$year])) {
                $sortedLessons[$year] = [
                    'sum'    => 0,
                    'debt'   => 0,
                    'cnt'    => 0,
                    'cnt_all' => 0,
                    'months' => [],
                ];
            }

            if (empty($sortedLessons[$year]['months'][$month])) {
                $sortedLessons[$year]['months'][$month] = [
                    'sum'     => 0,
                    'debt'    => 0,
                    'cnt'     => 0,
                    'cnt_all' => 0,
                    'lessons' => [],
                ];
            }

            $sortedLessons[$year]['months'][$month]['lessons'][] = [
                'id'            => $lesson->id,
                'subject'       => $lesson->subject,
                'topic_id'      => $lesson->topic_id ? (int) $lesson->topic_id : null,
                'subtopic_id'   => $lesson->subtopic_id ? (int) $lesson->subtopic_id : null,
                'topic_name'    => $lesson->topic_id ? ($topicNames[$lesson->topic_id] ?? null) : null,
                'subtopic_name' => $lesson->subtopic_id ? ($topicNames[$lesson->subtopic_id] ?? null) : null,
                'comment'       => $lesson->comment,
                'price'         => (int) $lesson->price,
                'duration'   => (int) $lesson->duration,
                'date'       => $date->toDateString(),
                'time'       => $lesson->time ? substr((string) $lesson->time, 0, 5) : null,
                'is_payed'   => (int) $lesson->is_payed,
                'is_future'  => (int) $lesson->is_future,
                'date_payed' => $lesson->date_payed
                    ? Carbon::parse($lesson->date_payed)->format('Y-m-d H:i')
                    : null,
            ];

            if (!empty($lesson->is_future)) {
                continue;
            }

            $sortedLessons[$year]['cnt_all']++;
            $sortedLessons[$year]['months'][$month]['cnt_all']++;

            if (!empty($lesson->is_payed)) {
                $sortedLessons[$year]['sum'] += (int) $lesson->price;
                $sortedLessons[$year]['cnt']++;
                $sortedLessons[$year]['months'][$month]['sum'] += (int) $lesson->price;
                $sortedLessons[$year]['months'][$month]['cnt']++;
            } else {
                $sortedLessons[$year]['debt'] += (int) $lesson->price;
                $sortedLessons[$year]['months'][$month]['debt'] += (int) $lesson->price;
            }
        }

        $topicsBySubject = [];

        foreach (Lesson::LESSON_SUBJECTS as $subject) {
            $topicsBySubject[$subject] = Topic::treeForSubject((int) $user->id, $subject);
        }

        $topicStates = $this->topicStatesForStudent((int) $student->id);

        return Inertia::render('StudentDetail', [
            'student' => [
                'id'            => $student->id,
                'name'          => $student->name,
                'class'         => $student->class,
                'current_class' => $student->current_class,
                'type'          => $student->type,
                'description'   => $student->description,
                'avatar'        => $student->avatar,
                'is_deleted'    => (int) $student->is_deleted,
                'created_at'    => $student->created_at
                    ? Carbon::parse($student->created_at)->toDateString()
                    : null,
            ],
            'summary' => [
                'total_lessons'     => $totalLessons,
                'paid_lessons'      => $paidLessons,
                'earned'            => $earned,
                'debt'              => $debt,
                'total_minutes'     => $totalMinutes,
                'first_lesson_date' => $firstLessonDate,
                'last_lesson_date'  => $lastLessonDate,
                'lessons_planned'   => $lessons->where('is_future', 1)->count(),
            ],
            'sortedLessons'   => $sortedLessons,
            'subjects'        => Lesson::LESSON_SUBJECTS,
            'topicsBySubject' => $topicsBySubject,
            'topicStates'     => $topicStates,
        ]);
    }

    /**
     * Состояния тем ученика и сводка по повторениям.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topicStatesForStudent(int $studentId): array
    {
        $studentTopics = StudentTopic::where('student_id', $studentId)->get();

        $reviews = TopicReview::where('student_id', $studentId)
            ->orderBy('reviewed_on', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $reviewsCount = [];
        $lastReviewComment = [];

        foreach ($reviews as $review) {
            $topicId = (int) $review->topic_id;
            $reviewsCount[$topicId] = ($reviewsCount[$topicId] ?? 0) + 1;

            if (!isset($lastReviewComment[$topicId])) {
                $lastReviewComment[$topicId] = $review->comment;
            }
        }

        $result = [];

        foreach ($studentTopics as $studentTopic) {
            $topicId = (int) $studentTopic->topic_id;

            $result[] = [
                'topic_id'            => $topicId,
                'status'              => $studentTopic->status,
                'mastered_at'         => $studentTopic->mastered_at
                    ? $studentTopic->mastered_at->toDateString()
                    : null,
                'last_reviewed_at'    => $studentTopic->last_reviewed_at
                    ? $studentTopic->last_reviewed_at->toDateString()
                    : null,
                'reviews_count'       => $reviewsCount[$topicId] ?? 0,
                'last_review_comment' => $lastReviewComment[$topicId] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Add or edit a student.
     */
    public function editStudent(): RedirectResponse
    {
        $rules = [
            'student_name'  => 'required',
            'student_class'  => 'required',
        ];

        $post = request()->post();

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $params = [
            'name'          => $post['student_name'],
            'class'         => $post['student_class'] ?? null,
            'type'          => $post['student_type'] ?? null,
            'description'   => $post['student_description'] ?? '',
            'avatar'        => !empty($post['student_avatar']) ? (string) $post['student_avatar'] : null,
        ];

        $str = 'add_student';

        if (!empty($post['student_id'])) {
            $str = 'edit_student';

            $student = Student::findOrFail($post['student_id']);

            $oldAvatar = $student->avatar;

            $student->name = $params['name'];
            $student->class = $params['class'];
            $student->type = $params['type'];
            $student->description = $params['description'];
            $student->avatar = $params['avatar'];

            $result = $student->save();

            if ($result && $oldAvatar !== $student->avatar) {
                \App\Image::deleteStoredAvatar($oldAvatar);
            }
        } else {
            $result = Auth::user()->addStudent($params);
        }

        if (empty($result)) {
            return back()->with('error', lng('error.' . $str));
        }

        return back()->with('success', lng('success.' . $str));
    }

    /**
     * Delete or restore a student.
     */
    public function deleteStudent(): RedirectResponse
    {
        $post = request()->post();

        $result = Auth::user()->editStudent($post);

        $str = 'edit_student';

        if (isset($post['is_deleted'])) {
            $str = $post['is_deleted'] ? 'delete_student' : 'return_student';
        }

        if (empty($result)) {
            return back()->with('error', lng('error.' . $str));
        }

        return back()->with('success', lng('success.' . $str));
    }
}