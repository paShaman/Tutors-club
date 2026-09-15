<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Role;
use App\Model\User;
use App\Services\TariffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminUserController extends Controller
{
    /**
     * Список пользователей с минимальной статистикой (доступ только у роли admin).
     *
     * Намеренно не кэшируется: админка холодная, а список должен сразу
     * показывать новых пользователей и смену их статусов.
     */
    public function index(TariffService $tariff): Response
    {
        return Inertia::render('Users', $this->buildPayload($tariff));
    }

    /**
     * Данные админской страницы: пользователи, агрегаты и справочник ролей.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(TariffService $tariff): array
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
                'role_titles'    => $user->roles->pluck('title')->all(),
                'plan'           => $tariff->plan($user),
                'plan_until'     => $tariff->until($user),
                'plan_period'    => $tariff->period($user),
                'telegram_linked'   => (string) $user->telegram_chat_id !== '',
                'telegram_username' => $user->telegram_username,
                'students_count' => (int) $user->students_count,
                'lessons_count'  => (int) ($stat?->lessons_count ?? 0),
                'topics_count'   => (int) $user->topics_count,
                'last_lesson_at' => $stat?->last_lesson_date ?? null,
            ];
        })->all();

        $roles = Role::query()
            ->orderBy('id')
            ->get(['title'])
            ->map(fn (Role $role): array => ['title' => (string) $role->title])
            ->all();

        return [
            'users' => $rows,
            'roles' => $roles,
            'plans' => array_keys((array) config('tariffs.plans', [])),
            'periods' => TariffService::PERIODS,
            'stats' => [
                'users'    => count($rows),
                'paid'     => count(array_filter($rows, fn (array $row): bool => $row['plan'] !== 'free')),
                'students' => array_sum(array_column($rows, 'students_count')),
                'lessons'  => array_sum(array_column($rows, 'lessons_count')),
            ],
        ];
    }

    /**
     * Назначение и снятие ролей пользователя.
     */
    public function updateRoles(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'user_id' => 'required|integer',
            'roles'   => 'nullable|array',
            'roles.*' => 'string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::find((int) $post['user_id']);

        if (!$user) {
            return back()->with('error', lng('error.admin_user_not_found'));
        }

        $requested = array_map('strval', (array) ($post['roles'] ?? []));

        // Не разрешаем снять с себя роль admin — иначе доступ к админке теряется безвозвратно.
        if ((int) $user->id === (int) Auth::id() && !in_array(Role::ADMIN, $requested, true)) {
            return back()->with('error', lng('error.admin_self_role'));
        }

        $available = Role::query()->pluck('id', 'title');
        $ids = [];

        foreach ($requested as $title) {
            if ($available->has($title)) {
                $ids[] = (int) $available->get($title);
            }
        }

        $user->roles()->sync($ids);

        User::flushRoleCache((int) $user->id);

        return back()->with('success', lng('success.admin_roles_updated'));
    }

    /**
     * Назначение подписки (тарифа) пользователю.
     */
    public function updateSubscription(TariffService $tariff): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'user_id'    => 'required|integer',
            'plan'       => ['required', 'string', Rule::in(array_keys((array) config('tariffs.plans', [])))],
            'period'     => ['required', 'string', Rule::in(TariffService::PERIODS)],
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::find((int) $post['user_id']);

        if (!$user) {
            return back()->with('error', lng('error.admin_user_not_found'));
        }

        $assigned = $tariff->assign(
            $user,
            (string) $post['plan'],
            (string) $post['period'],
            isset($post['expires_at']) ? (string) $post['expires_at'] : null,
        );

        if (!$assigned) {
            return back()->with('error', lng('error.admin_subscription'));
        }

        return back()->with('success', lng('success.admin_subscription'));
    }

    /**
     * Снятие активной подписки: пользователь возвращается на бесплатный тариф.
     */
    public function cancelSubscription(TariffService $tariff): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::find((int) $post['user_id']);

        if (!$user || !$tariff->cancel($user)) {
            return back()->with('error', lng('error.admin_subscription_cancel'));
        }

        return back()->with('success', lng('success.admin_subscription_cancel'));
    }
}
