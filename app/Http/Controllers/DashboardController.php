<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\User;
use App\Support\CacheKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $userId = (int) $user->getId();

        $props = Cache::remember(
            CacheKeys::dashboard($userId, CacheKeys::dataVersion($userId), (string) app()->getLocale()),
            now()->addMinutes(CacheKeys::TTL_PAGE_MINUTES),
            fn (): array => $this->buildDashboard($user),
        );

        return Inertia::render('Dashboard', $props);
    }

    /**
     * Данные дашборда: строятся один раз и кэшируются целиком.
     *
     * Все агрегаты собраны групповыми запросами, чтобы не делать
     * по несколько запросов на каждого ученика.
     *
     * @return array<string, mixed>
     */
    private function buildDashboard(User $user): array
    {
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

        // Дата последнего состоявшегося урока по каждому ученику — одним запросом.
        $lastLessonDates = collect();
        if (!empty($studentIds)) {
            $lastLessonDates = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->groupBy('student_id')
                ->selectRaw('student_id, MAX(date) as last_date')
                ->pluck('last_date', 'student_id');
        }

        // Sort students by last lesson date descending
        $studentsRaw = $studentsRaw
            ->sortByDesc(fn ($student) => $lastLessonDates[$student->id] ?? '0000-00-00')
            ->take(4);

        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

        // Уроки и оплаты текущего месяца по ученикам — одним запросом.
        $monthStats = collect();
        if (!empty($studentIds)) {
            $monthStats = Lesson::whereIn('student_id', $studentIds)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('date', '>=', $monthStart)
                ->where('date', '<=', $todayEnd)
                ->groupBy('student_id')
                ->selectRaw('student_id, COUNT(*) as total, SUM(CASE WHEN is_payed = 1 THEN 1 ELSE 0 END) as paid')
                ->get()
                ->keyBy('student_id');
        }

        $students = [];
        foreach ($studentsRaw as $student) {
            $stat = $monthStats->get($student->id);

            $students[] = [
                'id'           => $student->id,
                'slug'         => $student->slug,
                'name'         => $student->name,
                'studentClass' => $student->current_class,
                'totalLessons' => (int) ($stat->total ?? 0),
                'paidLessons'  => (int) ($stat->paid ?? 0),
                'gender'       => $student->gender,
                'color'        => $student->color,
            ];
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

        $dayNames = trans('messages.ui.days_short');
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

        $monthNames = trans('messages.ui.months_short');
        for ($m = 1; $m <= 12; $m++) {
            $earningsByMonth[] = [
                'month'  => $monthNames[$m - 1],
                'amount' => (int) ($monthlyEarnings->get($m)->total ?? 0),
            ];
        }

        return [
            'userName'           => $user->name ?: $user->email,
            'nextLesson'         => $nextLesson,
            'students'           => $students,
            'chartData'          => $chartData,
            'earningsByMonth'    => $earningsByMonth,
            'totalEarnings'      => $totalEarnings,
            'todaysLessonsCount' => $todaysLessonsCount,
        ];
    }
}
