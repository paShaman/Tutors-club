<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class SubjectController extends Controller
{
    /**
     * Панель управления предметами (доступ только у роли admin).
     */
    public function index(): Response
    {
        $subjects = Subject::query()
            ->withCount(['lessons', 'topics'])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Subject $subject): array => $subject->toAdminArray())
            ->all();

        return Inertia::render('Subjects', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Создание или редактирование предмета.
     */
    public function edit(): RedirectResponse
    {
        $post = request()->post();
        $locales = array_keys((array) config('locales.available', []));
        $requiredLocale = (string) config('app.fallback_locale', 'ru');

        $rules = [
            'subject_id' => 'nullable|integer',
            'position'   => 'nullable|integer|min:0|max:100000',
        ];

        foreach ($locales as $locale) {
            $rules['name.' . $locale] = ($locale === $requiredLocale ? 'required' : 'nullable') . '|string|max:100';
        }

        $validator = Validator::make($post, $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subjectId = !empty($post['subject_id']) ? (int) $post['subject_id'] : null;
        $subject = $subjectId !== null ? Subject::find($subjectId) : new Subject();

        if (!$subject) {
            return back()->with('error', lng('error.edit_subject'));
        }

        $names = [];

        foreach ($locales as $locale) {
            $value = trim((string) ($post['name'][$locale] ?? ''));

            if ($value !== '') {
                $names[$locale] = $value;
            }
        }

        $subject->name = $names;

        if ($subjectId === null) {
            // Код и slug генерируются из названия: код — внутренний ключ, slug — красивые URL.
            $base = (string) ($names[$requiredLocale] ?? reset($names));
            $subject->code = $this->uniqueValue('code', 'lesson_subject_' . (Str::slug($base) ?: 'subject'), '_');
            $subject->slug = $this->uniqueValue('slug', $this->slugBase((string) $subject->code), '-');
            $subject->is_deleted = 0;
        }

        if (!empty($post['position'])) {
            $subject->position = (int) $post['position'];
        } elseif ($subjectId === null) {
            $subject->position = (int) Subject::query()->max('position') + 100;
        }

        if (!$subject->save()) {
            return back()->with('error', lng('error.' . ($subjectId !== null ? 'edit_subject' : 'add_subject')));
        }

        Subject::flushCache();

        return back()->with('success', lng('success.' . ($subjectId !== null ? 'edit_subject' : 'add_subject')));
    }

    /**
     * Скрытие предмета (мягкое удаление: уроки и темы сохраняют ссылку).
     */
    public function delete(): RedirectResponse
    {
        return $this->setDeleted(true, 'error.delete_subject', 'success.delete_subject');
    }

    /**
     * Возврат скрытого предмета в список.
     */
    public function restore(): RedirectResponse
    {
        return $this->setDeleted(false, 'error.restore_subject', 'success.restore_subject');
    }

    private function setDeleted(bool $deleted, string $errorKey, string $successKey): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'subject_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subject = Subject::find((int) $post['subject_id']);

        if (!$subject) {
            return back()->with('error', lng($errorKey));
        }

        $subject->is_deleted = $deleted;

        if (!$subject->save()) {
            return back()->with('error', lng($errorKey));
        }

        Subject::flushCache();

        return back()->with('success', lng($successKey));
    }

    /**
     * Уникальное значение колонки с числовым суффиксом при совпадении.
     */
    private function uniqueValue(string $column, string $base, string $separator): string
    {
        $value = $base;
        $i = 2;

        while (Subject::query()->where($column, $value)->exists()) {
            $value = $base . $separator . $i;
            $i++;
        }

        return $value;
    }

    /**
     * Slug предмета — код без служебного префикса (lesson_subject_maths -> maths).
     */
    private function slugBase(string $code): string
    {
        return Str::slug((string) preg_replace('/^lesson_subject_/', '', $code)) ?: 'subject';
    }
}
