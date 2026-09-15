<?php

declare(strict_types=1);

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\StudentBotController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\UiKitController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// ─── Pages ──────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index'])
    ->name('home')
    ->middleware('auth');

Route::get('/login', [PageController::class, 'login'])
    ->name('login')
    ->middleware('guest');

Route::get('/register', [PageController::class, 'register'])
    ->name('register')
    ->middleware('guest');

Route::get('/settings', [PageController::class, 'settings'])
    ->name('settings')
    ->middleware('auth');

// ─── Calendar ───────────────────────────────────────────────
Route::get('/calendar', [CalendarController::class, 'index'])
    ->middleware('auth');

Route::get('/calendar/events', [CalendarController::class, 'getEvents'])
    ->middleware('auth');

// ─── Auth ───────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/auth/vk', [AuthController::class, 'vkontakte'])->middleware('guest');
Route::get('/auth/yandex', [AuthController::class, 'yandex'])
    ->name('auth.yandex')
    ->middleware('guest');
Route::get('/auth/yandex/callback', [AuthController::class, 'yandexCallback'])
    ->name('auth.yandex.callback');
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/auth', [AuthController::class, 'auth'])->name('auth')->middleware('signed');

// ─── User ───────────────────────────────────────────────────
Route::post('/user/settings', [UserController::class, 'settings'])->middleware('auth');
Route::post('/user/password', [UserController::class, 'password'])->middleware('auth');
Route::post('/user/locale', [UserController::class, 'locale'])->middleware('auth');
Route::post('/user/socials/link', [UserController::class, 'socialLink'])->middleware('auth');
Route::get('/user/socials/link/yandex', [AuthController::class, 'yandexLink'])
    ->name('auth.yandex.link')
    ->middleware('auth');
Route::post('/user/socials/unlink', [UserController::class, 'socialUnlink'])->middleware('auth');
Route::post('/user/telegram/refresh', [TelegramController::class, 'refresh'])->middleware('auth');
Route::post('/user/telegram/unlink', [TelegramController::class, 'unlink'])->middleware('auth');

// ─── Avatar ─────────────────────────────────────────────────
Route::post('/avatar/upload', [AvatarController::class, 'upload'])->middleware('auth');

// ─── Students ───────────────────────────────────────────────
Route::get('/students', [StudentController::class, 'getStudents'])->middleware('auth');
Route::get('/students/{student:slug}', [StudentController::class, 'show'])->middleware('auth');
Route::post('/students/edit', [StudentController::class, 'editStudent'])->middleware('auth');
Route::post('/students/delete', [StudentController::class, 'deleteStudent'])->middleware('auth');

// ─── Changelog ──────────────────────────────────────────
Route::get('/changelog', [ChangelogController::class, 'getChangelog'])
    ->middleware('auth');

// ─── Feedback ───────────────────────────────────────────────
Route::post('/feedback', [FeedbackController::class, 'send'])->middleware('auth');

// Вебхуки ботов идут без сессии, куки и Inertia: Telegram/MAX не шлют cookie,
// поэтому каждый апдейт создавал мусорную сессию в Redis, а падение Redis
// роняло вебхук до кода бота. Секрет заголовка и 200-ответ остаются ниже.
$botWebhookMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    SetLocale::class,
    HandleInertiaRequests::class,
];

Route::post('/telegram/webhook', [FeedbackController::class, 'webhook'])
    ->name('telegram.webhook')
    ->withoutMiddleware($botWebhookMiddleware);

Route::post('/max/webhook', [FeedbackController::class, 'maxWebhook'])
    ->name('max.webhook')
    ->withoutMiddleware($botWebhookMiddleware);

// ─── Telegram-бот управления учениками ──────────────────────
Route::post('/telegram/students/webhook', [StudentBotController::class, 'webhook'])
    ->name('telegram.students.webhook')
    ->withoutMiddleware($botWebhookMiddleware);

// ─── Admin ──────────────────────────────────────────────────
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->name('admin.users')
    ->middleware(['auth', 'admin']);

Route::post('/admin/users/roles', [AdminUserController::class, 'updateRoles'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/users/subscription', [AdminUserController::class, 'updateSubscription'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/users/subscription/cancel', [AdminUserController::class, 'cancelSubscription'])
    ->middleware(['auth', 'admin']);

Route::get('/admin/uikit', [UiKitController::class, 'index'])
    ->name('admin.uikit')
    ->middleware(['auth', 'admin']);

Route::get('/admin/promo', [PromoController::class, 'index'])
    ->name('admin.promo')
    ->middleware(['auth', 'admin']);

Route::post('/admin/promo/edit', [PromoController::class, 'edit'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/promo/delete', [PromoController::class, 'delete'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/promo/toggle', [PromoController::class, 'toggle'])
    ->middleware(['auth', 'admin']);

Route::get('/admin/subjects', [SubjectController::class, 'index'])
    ->name('admin.subjects')
    ->middleware(['auth', 'admin']);

Route::post('/admin/subjects/edit', [SubjectController::class, 'edit'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/subjects/delete', [SubjectController::class, 'delete'])
    ->middleware(['auth', 'admin']);

Route::post('/admin/subjects/restore', [SubjectController::class, 'restore'])
    ->middleware(['auth', 'admin']);

// ─── Lessons ────────────────────────────────────────────────
Route::get('/lessons', [LessonController::class, 'getLessons'])->middleware('auth')->name('lessons');
Route::get('/lessons/students/{student:slug}', [LessonController::class, 'getLessons'])->middleware('auth');
Route::get('/lessons/subjects/{subject:slug}', [LessonController::class, 'getLessons'])->middleware('auth');
Route::get('/lessons/students/{student:slug}/subjects/{subject:slug}', [LessonController::class, 'getLessons'])->middleware('auth')->withoutScopedBindings();
Route::post('/lessons/edit', [LessonController::class, 'editLesson'])->middleware('auth');
Route::post('/lessons/delete', [LessonController::class, 'deleteLesson'])->middleware('auth');
Route::post('/lessons/pay', [LessonController::class, 'payLesson'])->middleware('auth');

// ─── Planning ───────────────────────────────────────────────
Route::get('/planning', [PlanningController::class, 'index'])->middleware('auth')->name('planning');
Route::get('/planning/{subject:slug}', [PlanningController::class, 'index'])->middleware('auth');
Route::post('/topics/edit', [PlanningController::class, 'edit'])->middleware('auth');
Route::post('/topics/delete', [PlanningController::class, 'delete'])->middleware('auth');
Route::post('/topics/reorder', [PlanningController::class, 'reorder'])->middleware('auth');
Route::post('/student-topics/status', [PlanningController::class, 'setStatus'])->middleware('auth');
Route::post('/student-topics/review', [PlanningController::class, 'review'])->middleware('auth');