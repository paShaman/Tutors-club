<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    /**
     * Calendar page.
     */
    public function index(): Response
    {
        $students = Auth::user()->students()->get()->keyBy('id')->toArray();

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

        return Inertia::render('Calendar', [
            'students'          => $activeStudents,
            'lessonsSubjects'   => Lesson::LESSON_SUBJECTS,
            'defaultPrice'      => config('lesson.default_price'),
            'defaultDuration'   => config('lesson.default_duration'),
            'defaultDate'       => date('Y-m-d'),
        ]);
    }

    /**
     * Get calendar events as JSON (FullCalendar endpoint).
     */
    public function getEvents(): \Illuminate\Http\JsonResponse
    {
        $get = request()->query();

        $start = Carbon::parse($get['start']);
        $end = Carbon::parse($get['end']);

        $students = Auth::user()->students()->get()->keyBy('id')->toArray();
        $lessons = Lesson::where('is_deleted', 0)
            ->whereIn('student_id', array_keys($students))
            ->whereBetween('date', [$start, $end])
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
                    'subject'          => $lesson->subject,
                    'theme'            => $lesson->theme,
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

        return response()->json($events);
    }
}