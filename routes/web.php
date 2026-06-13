<?php

declare(strict_types=1);

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Social\GoogleController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

// ─── Pages ──────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index'])
    ->name('home')
    ->middleware('auth');

Route::get('/login', [PageController::class, 'login'])
    ->name('login')
    ->middleware('guest');

Route::get('/register', [PageController::class, 'register'])
    ->middleware('guest');

Route::get('/settings', [PageController::class, 'settings'])
    ->name('settings');

Route::get('/info/{page}', [PageController::class, 'page'])
    ->where('page', '[A-Za-z\-]+');

// ─── Calendar ───────────────────────────────────────────────
Route::get('/calendar', [CalendarController::class, 'index'])
    ->middleware('auth');

Route::get('/calendar/events', [CalendarController::class, 'getEvents'])
    ->middleware('auth');

// ─── Social ─────────────────────────────────────────────────
Route::get('/login/google', [GoogleController::class, 'redirectToProvider']);
Route::get('/login/google/callback', [GoogleController::class, 'handleProviderCallback']);

// ─── Auth ───────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/auth', [AuthController::class, 'auth'])->name('auth')->middleware('signed');

// ─── User ───────────────────────────────────────────────────
Route::post('/user/settings', [UserController::class, 'settings'])->middleware('auth');

// ─── Admin ──────────────────────────────────────────────────
Route::get('/admin', [PanelController::class, 'index'])->middleware('admin');
Route::get('/admin/user/list', [AdminUserController::class, 'usersList'])->middleware('admin');
Route::get('/admin/payment/list', [PaymentController::class, 'paymentsList'])->middleware('admin');
Route::post('/admin/payment/add', [PaymentController::class, 'paymentAdd'])->middleware('admin');

// ─── Students ───────────────────────────────────────────────
Route::get('/students', [StudentController::class, 'getStudents'])->middleware('auth');
Route::post('/students/edit', [StudentController::class, 'editStudent'])->middleware('auth');
Route::post('/students/delete', [StudentController::class, 'deleteStudent'])->middleware('auth');

// ─── Changelog ──────────────────────────────────────────
Route::get('/changelog', [ChangelogController::class, 'getChangelog'])
    ->middleware('auth');

// ─── Lessons ────────────────────────────────────────────────
Route::get('/lessons', [LessonController::class, 'getLessons'])->middleware('auth')->name('lessons');
Route::post('/lessons/edit', [LessonController::class, 'editLesson'])->middleware('auth');
Route::post('/lessons/delete', [LessonController::class, 'deleteLesson'])->middleware('auth');
Route::post('/lessons/pay', [LessonController::class, 'payLesson'])->middleware('auth');