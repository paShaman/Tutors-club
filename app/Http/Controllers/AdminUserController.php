<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Role;
use App\Model\User;
use App\Services\TariffService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminUserController extends Controller
{
    /**
     * Список пользователей с минимальной статистикой (доступ только у роли admin).
     */
    public function index(TariffService $tariff): Response
    {
        $users = User::query()
            ->with('roles')
            ->withCount([
                'students' => fn ($query) => $query->where('students.is_deleted', 0),
                'topics'   => fn ($query) => $query->where('topics.is_deleted', 0),
            ])
            ->orderByDesc('id')
            ->get();

        // Агрегаты по урокам считаем одним запросом, чтобы не плодить N+1 на каждого пользователя.
        $lessonStats = DB::table('students_to_users')
            ->join('lessons', 'lessons.student_id', '=', 'students_to_users.student_id')
            ->where('lessons.is_deleted', 0)
            ->groupBy('students_to_users.user_id')
            ->selectRaw('students_to_users.user_id, COUNT(*) as lessons_count, MAX(lessons.date) as last_lesson_date')
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function (User $user) use ($tariff, $lessonStats): array {
            $stat = $lessonStats->get($user->id);

            return [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'avatar'         => $user->avatar,
                'registered_at'  => $user->created_at?->toDateString(),
                'is_admin'       => $user->roles->contains('title', Role::ADMIN),
                'plan'           => $tariff->plan($user),
                'plan_until'     => $tariff->activeSubscription($user)?->expires_at?->toDateString(),
                'telegram_linked'   => (string) $user->telegram_chat_id !== '',
                'telegram_username' => $user->telegram_username,
                'students_count' => (int) $user->students_count,
                'lessons_count'  => (int) ($stat?->lessons_count ?? 0),
                'topics_count'   => (int) $user->topics_count,
                'last_lesson_at' => $stat?->last_lesson_date ?? null,
            ];
        })->all();

        return Inertia::render('Users', [
            'users' => $rows,
            'stats' => [
                'users'      => count($rows),
                'paid'       => count(array_filter($rows, fn (array $row): bool => $row['plan'] !== 'free')),
                'students'   => array_sum(array_column($rows, 'students_count')),
                'lessons'    => array_sum(array_column($rows, 'lessons_count')),
            ],
        ]);
    }
}
