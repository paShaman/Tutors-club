<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\Student;
use App\Model\StudentTopic;
use App\Model\Subject;
use App\Model\Topic;
use App\Model\User;
use App\Support\CacheKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    /**
     * Calendar page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $userId = (int) $user->id;

        // Пропсы страницы меняются только вместе с данными пользователя (ученики,
        // предметы, деревья тем, статусы тем), поэтому кэшируются по версии данных —
        // как дашборд и список уроков.
        $props = Cache::remember(
            CacheKeys::calendarPage($userId, CacheKeys::dataVersion($userId), (string) app()->getLocale()),
            now()->addMinutes(CacheKeys::TTL_PAGE_MINUTES),
            fn (): array => $this->buildIndexPayload($user),
        );

        return Inertia::render('Calendar', $props);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIndexPayload(User $user): array
    {
        $students = $user->students()->get()->keyBy('id')->toArray();

        // Remove deleted students
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

        $subjectCodes = Subject::codes();

        return [
            'students'          => $activeStudents,
            'lessonsSubjects'   => $subjectCodes,
            'subjectNames'      => Subject::nameMap(),
            'topicTree'         => Topic::treesBySubject((int) $user->id, $subjectCodes),
            'topicStatuses'     => StudentTopic::statusMapForStudents(array_keys($students)),
            'defaultPrice'      => config('lesson.default_price'),
            'defaultDuration'   => config('lesson.default_duration'),
            'defaultDate'       => date('Y-m-d'),
        ];
    }

    /**
     * Get calendar events as JSON (FullCalendar endpoint).
     *
     * Календарь запрашивает события после каждой мутации и при листании
     * периодов, поэтому результат кэшируется по диапазону и версии данных.
     */
    public function getEvents(): \Illuminate\Http\JsonResponse
    {
        $get = request()->query();

        $start = Carbon::parse($get['start']);
        $end = Carbon::parse($get['end']);

        $user = Auth::user();

        $events = Cache::remember(
            CacheKeys::calendarEvents(
                (int) $user->id,
                CacheKeys::dataVersion((int) $user->id),
                $start->toDateTimeString(),
                $end->toDateTimeString(),
            ),
            now()->addMinutes(CacheKeys::TTL_PAGE_MINUTES),
            fn (): array => $this->buildEvents($user, $start, $end),
        );

        return response()->json($events);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEvents(User $user, Carbon $start, Carbon $end): array
    {
        $students = $user->students()->get()->keyBy('id')->toArray();
        $lessons = Lesson::where('is_deleted', 0)
            ->whereIn('student_id', array_keys($students))
            ->whereBetween('date', [$start, $end])
            ->with('subject')
            ->orderBy('date', 'desc')->get();

        $events = [];

        foreach ($lessons as $lesson) {
            $student = $students[$lesson->student_id];

            $dateStart = Carbon::parse($lesson->date)->format('Y-m-d') . ($lesson->time ? ' ' . $lesson->time : '');
            $dateStart = Carbon::parse($dateStart);

            $event = [
                'id'    => $lesson->id,
                'display' => 'block',
                'title' => $student['name'],
                'start' => $dateStart->format($lesson->time ? "Y-m-d\TH:i:s" : "Y-m-d"),
                'extendedProps' => [
                    'student_id'       => $lesson->student_id,
                    'student_name'     => $student['name'],
                    'subject'          => $lesson->subject?->code,
                    'topic_id'         => $lesson->topic_id,
                    'subtopic_id'      => $lesson->subtopic_id,
                    'comment'          => $lesson->comment,
                    'price'            => $lesson->price,
                    'duration'         => $lesson->duration,
                    'is_payed'         => $lesson->is_payed,
                    'date'             => Carbon::parse($lesson->date)->format('Y-m-d'),
                    'date_payed'       => $lesson->date_payed ? Carbon::parse($lesson->date_payed)->format('Y-m-d') : null,
                    'time'             => $lesson->time,
                    'is_future'        => $lesson->is_future,
                ],
            ];

            if ($lesson->time) {
                $dateEnd = $dateStart->copy();
                $dateEnd = $dateEnd->addMinutes($lesson->duration);

                $event['end'] = $dateEnd->format("Y-m-d\TH:i:s");
            }

            if (!$lesson->is_payed) {
                $event['classNames'] = ['bg-danger', 'text-white'];
            }
            if ($lesson->is_future) {
                $event['classNames'] = ['bg-warning', ''];
            }

            $events[] = $event;
        }

        return $events;
    }
}