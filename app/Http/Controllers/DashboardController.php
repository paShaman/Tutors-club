<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    /**
     * Show the dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Collect IDs of students belonging to the authenticated user
        $studentIds = DB::table('students_to_users')
            ->where('user_id', $user->getId())
            ->pluck('student_id')
            ->toArray();

        // Load real students (only not deleted)
        $studentsRaw = collect();
        if (!empty($studentIds)) {
            $studentsRaw = \App\Model\Student::whereIn('id', $studentIds)
                ->where('is_deleted', 0)
                ->orderBy('type')
                ->get();
        }

        $avatarColors = [
            'from-purple-500 to-indigo-500',
            'from-blue-500 to-cyan-500',
            'from-pink-500 to-rose-500',
            'from-amber-500 to-orange-500',
            'from-emerald-500 to-teal-500',
            'from-red-500 to-orange-500',
        ];

        // For each student, get their last lesson date (for sorting)
        $studentLastLessonDates = [];
        foreach ($studentsRaw as $student) {
            $lastLesson = $student->lessons()
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->first();

            $studentLastLessonDates[$student->id] = $lastLesson ? $lastLesson->date : null;
        }

        // Sort students by last lesson date descending
        $studentsRaw = $studentsRaw->sortByDesc(function ($student) use ($studentLastLessonDates) {
            return $studentLastLessonDates[$student->id] ?? '0000-00-00';
        });

        // Take only first 4
        $studentsRaw = $studentsRaw->take(4);

        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

        $students = [];
        $colorIdx = 0;
        foreach ($studentsRaw as $student) {
            $totalLessons = $student->lessons()
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('date', '>=', $monthStart)
                ->where('date', '<=', $todayEnd)
                ->count();

            $paidLessons = $student->lessons()
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('date', '>=', $monthStart)
                ->where('date', '<=', $todayEnd)
                ->where('is_payed', 1)
                ->count();

            $students[] = [
                'id'           => $student->id,
                'name'         => $student->name,
                'studentClass' => $student->current_class,
                'totalLessons' => $totalLessons,
                'paidLessons'  => $paidLessons,
                'avatarColor'  => $avatarColors[$colorIdx % count($avatarColors)],
            ];
            $colorIdx++;
        }

        // Next upcoming lesson (future, not deleted) — fall back to last past lesson if none
        $isUpcoming = true;
        $nextLessonRaw = null;
        if (!empty($studentIds)) {
            $nextLessonRaw = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 1)
                ->whereDate('date', '>=', Carbon::today()->toDateString())
                ->orderBy('date')
                ->orderBy('time')
                ->first();

            // Fallback: last past lesson
            if (!$nextLessonRaw) {
                $isUpcoming = false;
                $nextLessonRaw = Lesson::whereIn('student_id', $studentIds)
                    ->where('is_deleted', 0)
                    ->where('is_future', 0)
                    ->orderBy('date', 'desc')
                    ->orderBy('time', 'desc')
                    ->first();
            }

            if ($nextLessonRaw) {
                $nextStudent = \App\Model\Student::find($nextLessonRaw->student_id);
                if ($nextStudent) {
                    $nextLessonRaw->student_name = $nextStudent->name;
                    $nextLessonRaw->student_class = $nextStudent->current_class;
                }
            }
        }

        $nextLesson = null;
        if ($nextLessonRaw) {
            $nextLesson = [
                'id'          => $nextLessonRaw->id,
                'studentName' => $nextLessonRaw->student_name ?? '',
                'studentClass' => $nextLessonRaw->student_class ?? '',
                'date'        => Carbon::parse($nextLessonRaw->date)->translatedFormat('j F Y'),
                'time'        => substr($nextLessonRaw->time, 0, 5),
                'duration'    => $nextLessonRaw->duration,
                'isPaid'      => (bool) $nextLessonRaw->is_payed,
                'isUpcoming'  => $isUpcoming,
            ];
        }

        // Weekly workload (last 7 days)
        $weekStart = Carbon::today()->subDays(6)->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $weekLessons = collect();
        if (!empty($studentIds)) {
            $weekLessons = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->whereBetween('date', [$weekStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                ->selectRaw("DATE(date) as lesson_date, SUM(CASE WHEN duration > 0 THEN duration ELSE 60 END) as total_minutes")
                ->groupBy(DB::raw('DATE(date)'))
                ->get()
                ->keyBy('lesson_date');
        }

        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $chartData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i)->toDateString();
            $minutes = (int) ($weekLessons->get($date)->total_minutes ?? 0);
            $chartData[] = [
                'day'   => $dayNames[$i],
                'hours' => round($minutes / 60, 1),
            ];
        }

        // Today's lessons count (real data, not future, not deleted)
        $todaysLessonsCount = 0;
        if (!empty($studentIds)) {
            $todaysLessonsCount = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->whereDate('date', Carbon::today()->toDateString())
                ->count();
        }

        // Monthly earnings (current month)
        $totalEarnings = 0;
        if (!empty($studentIds)) {
            $monthStart = Carbon::today()->startOfMonth();
            $totalEarnings = (int) Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('is_payed', 1)
                ->whereBetween('date', [$monthStart->startOfDay()->toDateTimeString(), Carbon::today()->endOfDay()->toDateTimeString()])
                ->sum('price');
        }

        // Earnings by month for current year
        $yearStart = Carbon::today()->startOfYear();
        $earningsByMonth = [];
        $monthlyEarnings = collect();
        if (!empty($studentIds)) {
            $monthlyEarnings = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('is_payed', 1)
                ->whereBetween('date', [$yearStart->toDateTimeString(), Carbon::today()->endOfDay()->toDateTimeString()])
                ->selectRaw('MONTH(date) as month, SUM(price) as total')
                ->groupBy(DB::raw('MONTH(date)'))
                ->orderBy(DB::raw('MONTH(date)'))
                ->get()
                ->keyBy('month');
        }

        $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
        for ($m = 1; $m <= 12; $m++) {
            $earningsByMonth[] = [
                'month'  => $monthNames[$m - 1],
                'amount' => (int) ($monthlyEarnings->get($m)->total ?? 0),
            ];
        }

        return Inertia::render('Dashboard', [
            'userName'           => $user->name ?: $user->email,
            'nextLesson'         => $nextLesson,
            'students'           => $students,
            'chartData'          => $chartData,
            'earningsByMonth'    => $earningsByMonth,
            'totalEarnings'      => $totalEarnings,
            'todaysLessonsCount' => $todaysLessonsCount,
        ]);
    }
}