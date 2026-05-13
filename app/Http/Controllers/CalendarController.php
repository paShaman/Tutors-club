<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
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
        return Inertia::render('Calendar');
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