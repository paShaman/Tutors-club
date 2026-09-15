<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Lesson;
use App\Model\Student;
use App\Model\Subject;
use App\Model\User;
use App\Support\UserCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Отдельный Telegram-бот для управления учениками: личный кабинет репетитора в чате.
 *
 * Читает и изменяет только данные владельца чата; привязка идёт по одноразовой
 * ссылке из настроек (таблица users: telegram_chat_id / telegram_link_code).
 * Добавление урока — пошаговый мастер, черновик хранится в users.telegram_state.
 */
final class StudentTelegramBotService
{
    private const API = 'https://api.telegram.org/bot';
    private const LINK_TTL_MINUTES = 15;
    private const WIZARD_TTL_MINUTES = 30;
    private const UNPAID_LIMIT = 8;
    private const PAID_LIMIT = 5;
    private const UPCOMING_LIMIT = 15;
    private const DURATION_OPTIONS = [45, 60, 90];

    public function configured(): bool
    {
        return $this->token() !== '' && $this->botUrl() !== null;
    }

    /**
     * Публичная ссылка на бота; с payload — deep-link для привязки.
     */
    public function botUrl(?string $payload = null): ?string
    {
        $username = ltrim((string) config('services.telegram_students.bot_username'), '@');

        if ($username === '') {
            return null;
        }

        return 'https://t.me/' . $username . ($payload !== null && $payload !== '' ? '?start=' . $payload : '');
    }

    /**
     * Данные для блока «Telegram» на странице настроек.
     *
     * @return array<string, mixed>
     */
    public function settingsPayload(User $user): array
    {
        $linked = $this->isLinked($user);
        $code = $linked ? null : $this->ensureLinkCode($user);

        return [
            'configured' => $this->configured(),
            'linked'     => $linked,
            'username'   => $user->telegram_username,
            'link_url'   => $code !== null ? $this->botUrl($code) : null,
            'code'       => $code,
            'ttl'        => self::LINK_TTL_MINUTES,
        ];
    }

    public function isLinked(User $user): bool
    {
        return (string) $user->telegram_chat_id !== '';
    }

    public function unlink(User $user): void
    {
        $user->telegram_chat_id = null;
        $user->telegram_username = null;
        $user->telegram_linked_at = null;
        $user->telegram_link_code = null;
        $user->telegram_link_code_expires_at = null;
        $user->telegram_state = null;
        $user->save();
    }

    /**
     * Сбрасывает код привязки, чтобы при следующем открытии настроек выпустить новый.
     */
    public function invalidateLinkCode(User $user): void
    {
        $user->telegram_link_code = null;
        $user->telegram_link_code_expires_at = null;
        $user->save();
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function handleUpdate(array $update): void
    {
        if (! $this->configured()) {
            return;
        }

        $callback = $update['callback_query'] ?? null;

        if (is_array($callback)) {
            $this->handleCallback($callback);

            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (is_array($message)) {
            $this->handleMessage($message);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));

        // Нетекстовые сообщения (фото, стикеры и т.п.) не обрабатываем.
        if ($text === '') {
            return;
        }

        $username = isset($message['from']['username']) ? (string) $message['from']['username'] : null;

        [$command, $args] = $this->parseCommand($text);

        if ($command === 'start') {
            $this->handleStart($chatId, $args, $username);

            return;
        }

        $user = $this->userByChatId($chatId);

        if ($user === null) {
            $this->sendMessage($chatId, lng('telegram.not_linked'));

            return;
        }

        $this->applyLocale($user);

        // Команда сбрасывает незавершённый черновик урока и выполняется как обычно.
        if (str_starts_with($text, '/')) {
            $this->clearWizard($user);

            if ($command === 'cancel') {
                $this->sendMessage($chatId, lng('telegram.lesson_cancelled'));

                return;
            }

            $this->dispatch($user, $chatId, $command, $args);

            return;
        }

        // Обычный текст — это ответ в мастере добавления урока.
        if ($this->handleWizardInput($user, $chatId, $text)) {
            return;
        }

        $this->sendMessage($chatId, lng('telegram.unknown_command', ['help' => lng('telegram.help')]));
    }

    private function handleStart(string $chatId, string $args, ?string $username): void
    {
        $user = $this->userByChatId($chatId);

        if ($args === '') {
            if ($user === null) {
                $this->sendMessage($chatId, lng('telegram.not_linked'));

                return;
            }

            // /start начинает диалог заново: незавершённый черновик урока больше не нужен.
            $this->clearWizard($user);
            $this->applyLocale($user);
            $this->sendMessage($chatId, lng('telegram.greeting', ['help' => lng('telegram.help')]));

            return;
        }

        $owner = $this->userByLinkCode($args);

        if ($owner === null) {
            $this->sendMessage($chatId, lng('telegram.link_invalid'));

            return;
        }

        if ($user !== null && (int) $user->id !== (int) $owner->id) {
            $this->sendMessage($chatId, lng('telegram.link_used'));

            return;
        }

        $owner->telegram_chat_id = $chatId;
        $owner->telegram_username = $username;
        $owner->telegram_linked_at = now();
        $owner->telegram_link_code = null;
        $owner->telegram_link_code_expires_at = null;
        $owner->save();

        $this->applyLocale($owner);
        $this->sendMessage($chatId, lng('telegram.link_success', [
            'name' => $owner->name !== '' ? $owner->name : (string) $owner->email,
            'help' => lng('telegram.help'),
        ]));
    }

    private function dispatch(User $user, string $chatId, string $command, string $args): void
    {
        match ($command) {
            'help', 'commands' => $this->sendMessage($chatId, lng('telegram.help')),
            'students'         => $this->sendStudents($user, $chatId),
            'student'          => $this->sendStudentCard($user, $chatId, $args),
            'finance'          => $this->sendFinance($user, $chatId, $args),
            'lessons'          => $this->sendUpcoming($user, $chatId),
            'debts'            => $this->sendDebts($user, $chatId),
            'lesson'           => $this->startLessonWizard($user, $chatId, $args),
            default            => $this->sendMessage($chatId, lng('telegram.unknown_command', ['help' => lng('telegram.help')])),
        };
    }

    // ─── Чтение ────────────────────────────────────────────────

    private function sendStudents(User $user, string $chatId): void
    {
        $students = $this->activeStudents($user);

        if ($students->isEmpty()) {
            $this->sendMessage($chatId, lng('telegram.students_empty'));

            return;
        }

        $lines = [lng('telegram.students_title', ['count' => $students->count()]), ''];

        foreach ($students as $index => $student) {
            $suffix = $student->current_class !== '' ? ' · ' . $student->current_class : '';
            $lines[] = ($index + 1) . '. ' . $student->name . $suffix;
        }

        $lines[] = '';
        $lines[] = lng('telegram.students_hint');

        $this->sendMessage($chatId, implode("\n", $lines), $this->numberKeyboard($students));
    }

    private function sendStudentCard(User $user, string $chatId, string $query): void
    {
        [$student, $ambiguous] = $this->findStudent($user, $query);

        if ($student === null) {
            $this->sendMessage($chatId, lng($ambiguous ? 'telegram.student_ambiguous' : 'telegram.student_not_found'));

            return;
        }

        $this->studentCard($user, $chatId, $student, null);
    }

    private function studentCard(User $user, string $chatId, Student $student, ?int $messageId): void
    {
        $text = lng('telegram.student_card', [
            'name'        => $student->name,
            'class'       => $student->current_class !== '' ? $student->current_class : '—',
            'gender'      => lng('telegram.gender_' . $this->genderKey($student->gender)),
            'type'        => $student->type ?: '—',
            'description' => $student->description ?: '—',
            'created'     => $student->created_at ? Carbon::parse($student->created_at)->format('d.m.Y') : '—',
        ]);

        $keyboard = [
            [
                ['text' => lng('telegram.finance_button'), 'callback_data' => 'f:' . $student->id],
                ['text' => lng('telegram.lessons_button'), 'callback_data' => 'l:' . $student->id],
            ],
            [
                ['text' => lng('telegram.students_button'), 'callback_data' => 'list'],
            ],
        ];

        $this->sendOrEdit($chatId, $messageId, $text, $keyboard);
    }

    private function sendFinance(User $user, string $chatId, string $query): void
    {
        [$student, $ambiguous] = $this->findStudent($user, $query);

        if ($student === null) {
            $this->sendMessage($chatId, lng($ambiguous ? 'telegram.student_ambiguous' : 'telegram.student_not_found'));

            return;
        }

        $this->finance($user, $chatId, $student, null);
    }

    private function finance(User $user, string $chatId, Student $student, ?int $messageId): void
    {
        $lessons = Lesson::where('student_id', $student->id)
            ->where('is_deleted', 0)
            ->orderBy('date')
            ->get();

        $total = 0;
        $paid = 0;
        $earned = 0;
        $debt = 0;
        $last = null;
        $unpaid = [];
        $paidLessons = [];

        foreach ($lessons as $lesson) {
            if (! empty($lesson->is_future)) {
                continue;
            }

            $total++;
            $price = (int) $lesson->price;
            $date = $lesson->date ? Carbon::parse($lesson->date) : null;

            if (! empty($lesson->is_payed)) {
                $paid++;
                $earned += $price;
                $paidLessons[] = $lesson;
            } else {
                $debt += $price;
                $unpaid[] = $lesson;
            }

            if ($date !== null && ($last === null || $date->gt($last))) {
                $last = $date;
            }
        }

        $lines = [
            lng('telegram.finance', [
                'name'   => $student->name,
                'total'  => $total,
                'paid'   => $paid,
                'earned' => $this->money($earned),
                'debt'   => $this->money($debt),
                'last'   => $last !== null ? $last->format('d.m.Y') : '—',
            ]),
        ];

        $keyboard = [
            [
                ['text' => lng('telegram.students_button'), 'callback_data' => 'list'],
            ],
        ];

        if ($unpaid === []) {
            $lines[] = '';
            $lines[] = lng('telegram.finance_debts_empty');
        } else {
            $lines[] = '';
            $lines[] = lng('telegram.finance_debts_title');

            foreach (array_slice($unpaid, -self::UNPAID_LIMIT) as $lesson) {
                $keyboard[] = [
                    ['text' => '✅ ' . $this->lessonLabel($lesson), 'callback_data' => 'pay:' . $lesson->id],
                ];
            }
        }

        // Оплаченные уроки показываем отдельным блоком, иначе снять оплату из бота негде.
        if ($paidLessons !== []) {
            $lines[] = '';
            $lines[] = lng('telegram.finance_paid_title');

            foreach (array_slice($paidLessons, -self::PAID_LIMIT) as $lesson) {
                $keyboard[] = [
                    ['text' => '↩️ ' . $this->lessonLabel($lesson), 'callback_data' => 'unpay:' . $lesson->id],
                ];
            }
        }

        $this->sendOrEdit($chatId, $messageId, implode("\n", $lines), $keyboard);
    }

    private function sendUpcoming(User $user, string $chatId, ?Student $student = null): void
    {
        $query = Lesson::whereIn('student_id', $user->students()->select('students.id'))
            ->where('is_deleted', 0)
            ->where('is_future', 1)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('time');

        if ($student !== null) {
            $query->where('student_id', $student->id);
        }

        $lessons = $query->limit(self::UPCOMING_LIMIT)->get();

        if ($lessons->isEmpty()) {
            $this->sendMessage($chatId, lng('telegram.lessons_empty'));

            return;
        }

        $names = $this->studentNames($user);
        $lines = [lng('telegram.lessons_title'), ''];
        $currentDate = null;

        foreach ($lessons as $lesson) {
            $date = $lesson->date ? Carbon::parse($lesson->date) : null;
            $key = $date !== null ? $date->toDateString() : '';

            if ($key !== $currentDate) {
                $currentDate = $key;
                $lines[] = '';
                $lines[] = '📆 ' . ($date !== null ? $date->format('d.m.Y') : '—');
            }

            $time = $lesson->time ? substr((string) $lesson->time, 0, 5) : '—';
            $name = $names[(int) $lesson->student_id] ?? '—';
            $subject = $lesson->subject?->localizedName() ?? '—';

            $lines[] = '  ' . $time . ' · ' . $name . ' · ' . $subject . ' · ' . $this->money((int) $lesson->price);
        }

        $this->sendMessage($chatId, implode("\n", $lines));
    }

    private function sendDebts(User $user, string $chatId): void
    {
        $students = $this->activeStudents($user);
        $lines = [lng('telegram.debts_title'), ''];
        $total = 0;

        foreach ($students as $student) {
            $debt = (int) Lesson::where('student_id', $student->id)
                ->where('is_deleted', 0)
                ->where('is_future', 0)
                ->where('is_payed', 0)
                ->sum('price');

            if ($debt <= 0) {
                continue;
            }

            $total += $debt;
            $lines[] = '• ' . $student->name . ' — ' . $this->money($debt);
        }

        if ($total === 0) {
            $this->sendMessage($chatId, lng('telegram.debts_empty'));

            return;
        }

        $lines[] = '';
        $lines[] = lng('telegram.debts_total', ['total' => $this->money($total)]);

        $this->sendMessage($chatId, implode("\n", $lines));
    }

    // ─── Мастер добавления урока ───────────────────────────────

    private function startLessonWizard(User $user, string $chatId, string $args): void
    {
        $index = trim($args);
        $student = ctype_digit($index) ? $this->studentByIndex($user, $index) : null;

        if ($student !== null) {
            $this->selectStudent($user, $chatId, $student);

            return;
        }

        $this->saveWizard($user, ['step' => 'student']);
        $this->askStudent($user, $chatId);
    }

    /**
     * Выбор ученика: если по нему уже есть уроки, предмет, цена, длительность и время
     * подставляются из последнего урока — остаётся только выбрать дату.
     */
    private function selectStudent(User $user, string $chatId, Student $student): void
    {
        $wizard = $this->wizard($user) ?? [];
        $wizard['student_id'] = (int) $student->id;
        $wizard['comment'] = '';

        $last = $this->lastLessonData((int) $student->id);

        if ($last !== null) {
            $wizard['subject_id'] = $last['subject_id'];
            $wizard['price'] = $last['price'] > 0 ? $last['price'] : (int) config('lesson.default_price', 3000);
            $wizard['duration'] = $last['duration'] > 0 ? $last['duration'] : (int) config('lesson.default_duration', 60);
            $wizard['time'] = $last['time'] ?? '15:00';
            $wizard['step'] = 'quick';
            $this->saveWizard($user, $wizard);
            $this->askQuick($user, $chatId);

            return;
        }

        $wizard['step'] = 'subject';
        $this->saveWizard($user, $wizard);
        $this->askSubject($user, $chatId);
    }

    private function askStudent(User $user, string $chatId): void
    {
        $students = $this->activeStudents($user);

        if ($students->isEmpty()) {
            $this->sendMessage($chatId, lng('telegram.students_empty'));

            return;
        }

        $keyboard = [];

        foreach ($students as $student) {
            $keyboard[] = [[
                'text'          => mb_substr($student->name, 0, 30),
                'callback_data' => 'w:s:' . $student->id,
            ]];
        }

        $keyboard[] = $this->cancelRow();

        $this->sendMessage($chatId, lng('telegram.lesson_choose_student'), $keyboard);
    }

    private function askSubject(User $user, string $chatId): void
    {
        $subjects = Subject::active();

        if ($subjects->isEmpty()) {
            $this->sendMessage($chatId, lng('telegram.subject_not_found', ['subjects' => '—']));

            return;
        }

        $keyboard = [];

        foreach ($subjects as $subject) {
            $keyboard[] = [[
                'text'          => mb_substr($subject->localizedName(), 0, 30),
                'callback_data' => 'w:b:' . $subject->id,
            ]];
        }

        $keyboard[] = $this->cancelRow();

        $this->sendMessage($chatId, lng('telegram.lesson_choose_subject', [
            'name' => $this->studentNameFromWizard($user) ?? '—',
        ]), $keyboard);
    }

    /**
     * Быстрый экран: всё, кроме даты, уже подставлено из прошлого урока.
     */
    private function askQuick(User $user, string $chatId): void
    {
        $wizard = $this->wizard($user) ?? [];
        $subject = $this->subjectFromWizard($wizard);
        $comment = trim((string) ($wizard['comment'] ?? ''));
        $time = (string) ($wizard['time'] ?? '15:00');

        $keyboard = [
            [
                ['text' => lng('telegram.lesson_today_button', ['time' => $time]), 'callback_data' => 'w:q:today'],
                ['text' => lng('telegram.lesson_tomorrow_button', ['time' => $time]), 'callback_data' => 'w:q:tomorrow'],
            ],
            [
                ['text' => lng('telegram.lesson_custom_time_button'), 'callback_data' => 'w:q:custom'],
            ],
            [$this->paidToggle($wizard)],
            [
                ['text' => lng('telegram.lesson_comment_button'), 'callback_data' => 'w:q:comment'],
                ['text' => lng('telegram.lesson_edit_button'), 'callback_data' => 'w:e'],
            ],
            $this->cancelRow(),
        ];

        $this->sendMessage($chatId, lng('telegram.lesson_quick', [
            'name'     => $this->studentNameFromWizard($user) ?? '—',
            'subject'  => $subject?->localizedName() ?? '—',
            'price'    => $this->money((int) ($wizard['price'] ?? 0)),
            'duration' => (int) ($wizard['duration'] ?? 0),
            'paid'     => $this->paidLine($wizard),
            'comment'  => $comment !== '' ? "\n📝 " . $comment : '',
        ]), $keyboard);
    }

    private function askDatetime(User $user, string $chatId): void
    {
        $wizard = $this->wizard($user) ?? [];
        $subject = $this->subjectFromWizard($wizard);
        $time = (string) ($wizard['time'] ?? '15:00');

        $keyboard = [
            [
                ['text' => lng('telegram.lesson_today_button', ['time' => $time]), 'callback_data' => 'w:d:today'],
                ['text' => lng('telegram.lesson_tomorrow_button', ['time' => $time]), 'callback_data' => 'w:d:tomorrow'],
            ],
            $this->cancelRow(),
        ];

        $this->sendMessage($chatId, lng('telegram.lesson_ask_datetime', [
            'name'    => $this->studentNameFromWizard($user) ?? '—',
            'subject' => $subject?->localizedName() ?? '—',
        ]), $keyboard);
    }

    private function askPrice(User $user, string $chatId): void
    {
        $wizard = $this->wizard($user) ?? [];
        $suggested = $this->suggestedPrice((int) ($wizard['student_id'] ?? 0));

        $keyboard = [
            [
                [
                    'text'          => lng('telegram.lesson_price_button', ['price' => $this->money($suggested)]),
                    'callback_data' => 'w:p:' . $suggested,
                ],
            ],
            [
                ['text' => lng('telegram.lesson_price_custom_button'), 'callback_data' => 'w:p:c'],
            ],
            $this->cancelRow(),
        ];

        $this->sendMessage($chatId, lng('telegram.lesson_ask_price'), $keyboard);
    }

    private function askDuration(User $user, string $chatId): void
    {
        $row = [];

        foreach (self::DURATION_OPTIONS as $minutes) {
            $row[] = [
                'text'          => lng('telegram.lesson_minutes', ['minutes' => $minutes]),
                'callback_data' => 'w:u:' . $minutes,
            ];
        }

        $this->sendMessage($chatId, lng('telegram.lesson_ask_duration'), [$row, $this->cancelRow()]);
    }

    private function askConfirm(User $user, string $chatId): void
    {
        $wizard = $this->wizard($user) ?? [];
        $subject = $this->subjectFromWizard($wizard);
        $comment = trim((string) ($wizard['comment'] ?? ''));

        $text = lng('telegram.lesson_confirm', [
            'name'     => $this->studentNameFromWizard($user) ?? '—',
            'subject'  => $subject?->localizedName() ?? '—',
            'date'     => isset($wizard['date']) ? Carbon::parse((string) $wizard['date'])->format('d.m.Y') : '—',
            'time'     => (string) ($wizard['time'] ?? '—'),
            'price'    => $this->money((int) ($wizard['price'] ?? 0)),
            'duration' => (int) ($wizard['duration'] ?? 0),
            'paid'     => $this->paidLine($wizard),
            'comment'  => $comment !== '' ? "\n📝 " . $comment : '',
        ]);

        $keyboard = [
            [
                ['text' => lng('telegram.lesson_confirm_button'), 'callback_data' => 'w:c'],
            ],
            [$this->paidToggle($wizard)],
            [
                ['text' => lng('telegram.lesson_comment_button'), 'callback_data' => 'w:cm'],
                ['text' => lng('telegram.lesson_cancel_button'), 'callback_data' => 'w:x'],
            ],
        ];

        $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * @param  array<string, mixed>  $wizard
     */
    private function createWizardLesson(User $user, string $chatId, array $wizard): void
    {
        $student = $this->studentById($user, (int) ($wizard['student_id'] ?? 0));
        $subject = $this->subjectFromWizard($wizard);
        $date = isset($wizard['date']) ? $this->parseDate((string) $wizard['date']) : null;
        $price = (int) ($wizard['price'] ?? 0);

        if ($student === null || $subject === null || $date === null || $price <= 0) {
            $this->clearWizard($user);
            $this->sendMessage($chatId, lng('telegram.lesson_wizard_expired'));

            return;
        }

        if (! app(TariffService::class)->canUse($user, 'lessons')) {
            $this->clearWizard($user);
            $this->sendMessage($chatId, lng('telegram.tariff_limit'));

            return;
        }

        $duration = (int) ($wizard['duration'] ?? 0);

        if ($duration <= 0) {
            $duration = (int) config('lesson.default_duration', 60);
        }

        $time = (string) ($wizard['time'] ?? '00:00');
        $comment = mb_substr(trim((string) ($wizard['comment'] ?? '')), 0, 500);
        $isPaid = ! empty($wizard['is_payed']);

        $lesson = new Lesson([
            'subject_id' => $subject->id,
            'price'      => $price,
            'duration'   => $duration,
            'date'       => $date->toDateString(),
            'time'       => $time,
            'is_payed'   => $isPaid ? 1 : 0,
            'date_payed' => $isPaid ? Carbon::now() : null,
            // Запланированным считаем только урок на будущую дату: урок на сегодня
            // бот добавляет уже по факту, иначе он навсегда выпадет из статистики.
            'is_future'  => $date->gt(Carbon::today()) ? 1 : 0,
            'comment'    => $comment,
            'is_deleted' => 0,
        ]);

        $student->lessons()->save($lesson);

        // Бот пишет в те же таблицы, что и кабинет, поэтому обязан гасить кэш.
        UserCache::flush($user);

        $this->clearWizard($user);

        // Кнопка отмены — на случай, если урок добавили по ошибке (из бота удалить иначе негде).
        $keyboard = [
            [
                ['text' => lng('telegram.lesson_undo_button'), 'callback_data' => 'w:undo:' . $lesson->id],
            ],
        ];

        $this->sendMessage($chatId, lng('telegram.lesson_added', [
            'name'     => $student->name,
            'subject'  => $subject->localizedName(),
            'date'     => $date->format('d.m.Y'),
            'time'     => $time,
            'price'    => $this->money($price),
            'duration' => $duration,
            'paid'     => $isPaid ? "\n" . lng('telegram.lesson_paid_yes') : '',
            'comment'  => $comment !== '' ? "\n📝 " . $comment : '',
        ]), $keyboard);
    }

    /**
     * Текстовый ввод в мастере. Возвращает true, если сообщение обработано.
     */
    private function handleWizardInput(User $user, string $chatId, string $text): bool
    {
        $wizard = $this->wizard($user);

        if ($wizard === null) {
            return false;
        }

        switch ((string) ($wizard['step'] ?? '')) {
            case 'datetime':
                [$date, $time] = $this->parseDateTimeInput($text);

                if ($date === null || $time === null) {
                    $this->sendMessage($chatId, lng('telegram.lesson_datetime_invalid'));

                    return true;
                }

                $wizard['date'] = $date->toDateString();
                $wizard['time'] = $time;
                $wizard['step'] = 'price';
                $this->saveWizard($user, $wizard);
                $this->askPrice($user, $chatId);

                return true;

            case 'datetime_quick':
                [$date, $time] = $this->parseDateTimeInput($text);

                if ($date === null || $time === null) {
                    $this->sendMessage($chatId, lng('telegram.lesson_datetime_invalid'));

                    return true;
                }

                $wizard['date'] = $date->toDateString();
                $wizard['time'] = $time;
                $wizard['step'] = 'confirm';
                $this->saveWizard($user, $wizard);
                $this->createWizardLesson($user, $chatId, $wizard);

                return true;

            case 'price_custom':
                $price = (int) preg_replace('/\D/', '', $text);

                if ($price <= 0) {
                    $this->sendMessage($chatId, lng('telegram.price_invalid'));

                    return true;
                }

                $wizard['price'] = $price;
                $wizard['step'] = 'duration';
                $this->saveWizard($user, $wizard);
                $this->askDuration($user, $chatId);

                return true;

            case 'comment_quick':
                $wizard['comment'] = $this->normalizeComment($text);
                $wizard['step'] = 'quick';
                $this->saveWizard($user, $wizard);
                $this->askQuick($user, $chatId);

                return true;

            case 'comment':
                $wizard['comment'] = $this->normalizeComment($text);
                $wizard['step'] = 'confirm';
                $this->saveWizard($user, $wizard);
                $this->askConfirm($user, $chatId);

                return true;

            case 'quick':
                $this->askQuick($user, $chatId);

                return true;

            case 'student':
                $this->askStudent($user, $chatId);

                return true;

            case 'subject':
                $this->askSubject($user, $chatId);

                return true;

            case 'price':
                $this->askPrice($user, $chatId);

                return true;

            case 'duration':
                $this->askDuration($user, $chatId);

                return true;

            case 'confirm':
                $this->askConfirm($user, $chatId);

                return true;
        }

        return true;
    }

    private function handleWizardCallback(User $user, string $chatId, string $value): void
    {
        [$action, $arg] = array_pad(explode(':', $value, 2), 2, '');

        if ($action === 'x') {
            $this->clearWizard($user);
            $this->sendMessage($chatId, lng('telegram.lesson_cancelled'));

            return;
        }

        // Отмена только что добавленного урока — мастер к этому моменту уже очищен.
        if ($action === 'undo') {
            $this->undoLesson($user, $chatId, (int) $arg);

            return;
        }

        $wizard = $this->wizard($user);

        if ($wizard === null) {
            $this->sendMessage($chatId, lng('telegram.lesson_wizard_expired'));

            return;
        }

        switch ($action) {
            case 's':
                $student = $this->studentById($user, (int) $arg);

                if ($student === null) {
                    $this->sendMessage($chatId, lng('telegram.student_not_found'));

                    return;
                }

                $this->selectStudent($user, $chatId, $student);
                break;

            case 'b':
                $subject = Subject::where('id', (int) $arg)->where('is_deleted', 0)->first();

                if ($subject === null) {
                    $this->sendMessage($chatId, lng('telegram.subject_not_found', [
                        'subjects' => implode(', ', Subject::nameMap()),
                    ]));

                    return;
                }

                $wizard['subject_id'] = (int) $subject->id;
                $wizard['step'] = 'datetime';
                $this->saveWizard($user, $wizard);
                $this->askDatetime($user, $chatId);
                break;

            case 'd':
                $date = $arg === 'tomorrow' ? Carbon::tomorrow() : Carbon::today();

                $wizard['date'] = $date->toDateString();
                $wizard['time'] = (string) ($wizard['time'] ?? '15:00');
                $wizard['step'] = 'price';
                $this->saveWizard($user, $wizard);
                $this->askPrice($user, $chatId);
                break;

            // Быстрый режим: параметры уже подставлены — дата сразу создаёт урок.
            case 'q':
                switch ($arg) {
                    case 'today':
                    case 'tomorrow':
                        $date = $arg === 'tomorrow' ? Carbon::tomorrow() : Carbon::today();
                        $wizard['date'] = $date->toDateString();
                        $wizard['time'] = (string) ($wizard['time'] ?? '15:00');
                        $wizard['step'] = 'confirm';
                        $this->saveWizard($user, $wizard);
                        $this->createWizardLesson($user, $chatId, $wizard);
                        break;

                    case 'custom':
                        $wizard['step'] = 'datetime_quick';
                        $this->saveWizard($user, $wizard);
                        $this->sendMessage($chatId, lng('telegram.lesson_ask_datetime', [
                            'name'    => $this->studentNameFromWizard($user) ?? '—',
                            'subject' => $this->subjectFromWizard($wizard)?->localizedName() ?? '—',
                        ]));
                        break;

                    case 'comment':
                        $wizard['step'] = 'comment_quick';
                        $this->saveWizard($user, $wizard);
                        $this->sendMessage($chatId, lng('telegram.lesson_comment_prompt'));
                        break;
                }
                break;

            // Переключатель «оплачен»: перерисовываем тот экран, где он нажат.
            case 'y':
                $wizard['is_payed'] = empty($wizard['is_payed']);
                $this->saveWizard($user, $wizard);

                if (($wizard['step'] ?? '') === 'confirm') {
                    $this->askConfirm($user, $chatId);
                } else {
                    $this->askQuick($user, $chatId);
                }
                break;

            // Полный режим: подставляем параметры вручную.
            case 'e':
                $wizard['step'] = 'subject';
                $this->saveWizard($user, $wizard);
                $this->askSubject($user, $chatId);
                break;

            case 'cm':
                $wizard['step'] = 'comment';
                $this->saveWizard($user, $wizard);
                $this->sendMessage($chatId, lng('telegram.lesson_comment_prompt'));
                break;

            case 'p':
                if ($arg === 'c') {
                    $wizard['step'] = 'price_custom';
                    $this->saveWizard($user, $wizard);
                    $this->sendMessage($chatId, lng('telegram.lesson_price_custom'));

                    return;
                }

                $wizard['price'] = (int) preg_replace('/\D/', '', $arg);
                $wizard['step'] = 'duration';
                $this->saveWizard($user, $wizard);
                $this->askDuration($user, $chatId);
                break;

            case 'u':
                $wizard['duration'] = (int) $arg;
                $wizard['step'] = 'confirm';
                $this->saveWizard($user, $wizard);
                $this->askConfirm($user, $chatId);
                break;

            case 'c':
                $this->createWizardLesson($user, $chatId, $wizard);
                break;
        }
    }

    // ─── Telegram API ──────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $callback
     */
    private function handleCallback(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $messageId = isset($callback['message']['message_id']) ? (int) $callback['message']['message_id'] : null;
        $data = (string) ($callback['data'] ?? '');

        if ($chatId === '' || $data === '') {
            return;
        }

        $user = $this->userByChatId($chatId);

        if ($user === null) {
            $this->answerCallback($callbackId);
            $this->sendMessage($chatId, lng('telegram.not_linked'));

            return;
        }

        $this->applyLocale($user);

        [$action, $value] = array_pad(explode(':', $data, 2), 2, '');

        switch ($action) {
            case 'list':
                $this->answerCallback($callbackId);
                $this->sendStudents($user, $chatId);
                break;

            case 's':
                $this->answerCallback($callbackId);
                $this->openStudentCard($user, $chatId, (int) $value, $messageId);
                break;

            case 'f':
                $this->answerCallback($callbackId);
                $this->openFinance($user, $chatId, (int) $value, $messageId);
                break;

            case 'l':
                $this->answerCallback($callbackId);
                $student = $this->studentById($user, (int) $value);

                if ($student === null) {
                    $this->sendMessage($chatId, lng('telegram.student_not_found'));
                    break;
                }

                $this->sendUpcoming($user, $chatId, $student);
                break;

            case 'pay':
                $this->payLesson($user, $chatId, (int) $value, $callbackId, $messageId);
                break;

            case 'unpay':
                $this->payLesson($user, $chatId, (int) $value, $callbackId, $messageId, false);
                break;

            case 'w':
                $this->answerCallback($callbackId);
                $this->handleWizardCallback($user, $chatId, $value);
                break;

            default:
                $this->answerCallback($callbackId);
        }
    }

    private function openStudentCard(User $user, string $chatId, int $studentId, ?int $messageId): void
    {
        $student = $this->studentById($user, $studentId);

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        $this->studentCard($user, $chatId, $student, $messageId);
    }

    private function openFinance(User $user, string $chatId, int $studentId, ?int $messageId): void
    {
        $student = $this->studentById($user, $studentId);

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        $this->finance($user, $chatId, $student, $messageId);
    }

    /**
     * Отметка оплаты урока. $paid = false снимает оплату — тоже явно, чтобы
     * повторное нажатие не «перещёлкивало» статус.
     */
    private function payLesson(User $user, string $chatId, int $lessonId, string $callbackId, ?int $messageId, bool $paid = true): void
    {
        $lesson = $this->ownedLesson($user, $lessonId);

        if ($lesson === null) {
            $this->answerCallback($callbackId, lng('telegram.lesson_not_found'));

            return;
        }

        $lesson->is_payed = $paid ? 1 : 0;
        $lesson->date_payed = $paid ? Carbon::now() : null;
        $lesson->save();

        UserCache::flush($user);

        $date = $lesson->date ? Carbon::parse($lesson->date)->format('d.m.Y') : '';

        $this->answerCallback($callbackId, lng(
            $paid ? 'telegram.lesson_paid' : 'telegram.lesson_unpaid',
            ['date' => $date],
        ));

        $student = $this->studentById($user, (int) $lesson->student_id);

        if ($student !== null) {
            $this->finance($user, $chatId, $student, $messageId);
        }
    }

    /**
     * @param  array<int, array<int, array<string, string>>>  $keyboard
     */
    private function sendMessage(string $chatId, string $text, array $keyboard = []): bool
    {
        $payload = [
            'chat_id'                  => $chatId,
            'text'                     => mb_substr($text, 0, 4000),
            'disable_web_page_preview' => true,
        ];

        if ($keyboard !== []) {
            $payload['reply_markup'] = ['inline_keyboard' => $keyboard];
        }

        return $this->call('sendMessage', $payload);
    }

    /**
     * @param  array<int, array<int, array<string, string>>>  $keyboard
     */
    private function sendOrEdit(string $chatId, ?int $messageId, string $text, array $keyboard = []): void
    {
        if ($messageId !== null && $this->editMessage($chatId, $messageId, $text, $keyboard)) {
            return;
        }

        $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * @param  array<int, array<int, array<string, string>>>  $keyboard
     */
    private function editMessage(string $chatId, int $messageId, string $text, array $keyboard = []): bool
    {
        return $this->call('editMessageText', [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => mb_substr($text, 0, 4000),
            'disable_web_page_preview' => true,
            'reply_markup'             => ['inline_keyboard' => $keyboard],
        ]);
    }

    private function answerCallback(string $callbackId, string $text = ''): void
    {
        if ($callbackId === '') {
            return;
        }

        $payload = ['callback_query_id' => $callbackId];

        if ($text !== '') {
            $payload['text'] = mb_substr($text, 0, 200);
        }

        $this->call('answerCallbackQuery', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(string $method, array $payload): bool
    {
        try {
            return Http::asJson()
                ->timeout(10)
                ->post(self::API . $this->token() . '/' . $method, $payload)
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    // ─── Данные и разбор ───────────────────────────────────────

    private function userByChatId(string $chatId): ?User
    {
        return User::where('telegram_chat_id', $chatId)->first();
    }

    private function userByLinkCode(string $code): ?User
    {
        return User::where('telegram_link_code', $code)
            ->whereNotNull('telegram_link_code_expires_at')
            ->where('telegram_link_code_expires_at', '>', now())
            ->first();
    }

    private function ensureLinkCode(User $user): string
    {
        $code = (string) $user->telegram_link_code;
        $expires = $user->telegram_link_code_expires_at;

        if ($code !== '' && $expires !== null && Carbon::parse($expires)->isFuture()) {
            return $code;
        }

        $code = Str::random(40);
        $user->telegram_link_code = $code;
        $user->telegram_link_code_expires_at = now()->addMinutes(self::LINK_TTL_MINUTES);
        $user->save();

        return $code;
    }

    private function applyLocale(User $user): void
    {
        $locale = (string) $user->locale;

        if ($locale !== '' && array_key_exists($locale, config('locales.available', []))) {
            app()->setLocale($locale);
        }
    }

    /**
     * В боте участвуют только обычные ученики: особые группы/организации (type)
     * не показываем, не ищем и не даём выбрать.
     *
     * @return Collection<int, Student>
     */
    private function activeStudents(User $user): Collection
    {
        return $this->regularStudents($user)
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get();
    }

    private function studentById(User $user, int $studentId): ?Student
    {
        return $this->regularStudents($user)->where('students.id', $studentId)->first();
    }

    private function regularStudents(User $user): BelongsToMany
    {
        return $user->students()->where(function ($query): void {
            $query->whereNull('type')->orWhere('type', '');
        });
    }

    private function studentByIndex(User $user, string $index): ?Student
    {
        if (! ctype_digit($index)) {
            return null;
        }

        return $this->activeStudents($user)->get((int) $index - 1);
    }

    /**
     * Разбор запроса ученика: номер из списка или часть имени.
     *
     * @return array{0: ?Student, 1: bool}  ученик и признак неоднозначности
     */
    private function findStudent(User $user, string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [null, false];
        }

        $students = $this->activeStudents($user);

        if (ctype_digit($query)) {
            return [$students->get((int) $query - 1), false];
        }

        $needle = mb_strtolower($query);
        $matches = $students
            ->filter(fn (Student $student): bool => mb_strpos(mb_strtolower((string) $student->name), $needle) !== false)
            ->values();

        if ($matches->count() === 1) {
            return [$matches->first(), false];
        }

        return [null, $matches->count() > 1];
    }

    /**
     * @return array<int, string>  id ученика => имя
     */
    private function studentNames(User $user): array
    {
        return $user->students()->pluck('name', 'students.id')->all();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return array<int, array<int, array<string, string>>>
     */
    private function numberKeyboard(Collection $students): array
    {
        $keyboard = [];
        $row = [];

        foreach ($students->values() as $index => $student) {
            $row[] = ['text' => (string) ($index + 1), 'callback_data' => 's:' . $student->id];

            if (count($row) === 5) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        return $keyboard;
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function cancelRow(): array
    {
        return [
            ['text' => lng('telegram.lesson_cancel_button'), 'callback_data' => 'w:x'],
        ];
    }

    /**
     * Строка со статусом оплаты черновика урока.
     *
     * @param  array<string, mixed>  $wizard
     */
    private function paidLine(array $wizard): string
    {
        return lng(! empty($wizard['is_payed']) ? 'telegram.lesson_paid_yes' : 'telegram.lesson_paid_no');
    }

    /**
     * Кнопка-переключатель «оплачен» на экранах мастера урока.
     *
     * @param  array<string, mixed>  $wizard
     * @return array{text: string, callback_data: string}
     */
    private function paidToggle(array $wizard): array
    {
        return [
            'text'          => lng(! empty($wizard['is_payed']) ? 'telegram.lesson_unpay_button' : 'telegram.lesson_pay_button'),
            'callback_data' => 'w:y',
        ];
    }

    /**
     * @return array{0: string, 1: string}  команда без слэша и остаток строки
     */
    private function parseCommand(string $text): array
    {
        // Команда — только текст со слэшем: обычное сообщение («start», имя ученика,
        // комментарий к уроку) не должно запускать команду.
        if (! str_starts_with($text, '/')) {
            return ['', ''];
        }

        $parts = preg_split('/\s+/u', trim(mb_substr($text, 1))) ?: [];
        $command = mb_strtolower((string) array_shift($parts));

        // /help@bot_name → help
        $command = explode('@', $command)[0];

        return [$command, implode(' ', $parts)];
    }

    /**
     * @return array{0: ?Carbon, 1: ?string}
     */
    private function parseDateTimeInput(string $text): array
    {
        $parts = preg_split('/\s+/u', trim($text)) ?: [];

        if (count($parts) < 2) {
            return [null, null];
        }

        return [$this->parseDate($parts[0]), $this->parseTime($parts[1])];
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = mb_strtolower(trim($value));
        $today = Carbon::today();

        if ($value === '') {
            return null;
        }

        if (in_array($value, ['сегодня', 'today'], true)) {
            return $today;
        }

        if (in_array($value, ['завтра', 'tomorrow'], true)) {
            return $today->copy()->addDay();
        }

        foreach (['d.m.Y', 'd.m.y', 'Y-m-d', 'd/m/Y'] as $format) {
            $parsed = \DateTime::createFromFormat('!' . $format, $value);

            if ($parsed instanceof \DateTime && $parsed->format($format) === $value) {
                return Carbon::instance($parsed);
            }
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $value, $matches) === 1) {
            $date = Carbon::create($today->year, (int) $matches[2], (int) $matches[1])->startOfDay();

            if ($date->lt($today)) {
                $date->addYear();
            }

            return $date;
        }

        return null;
    }

    private function parseTime(string $value): ?string
    {
        if (preg_match('/^(\d{1,2})(?::(\d{2}))?$/', trim($value), $matches) !== 1) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = isset($matches[2]) ? (int) $matches[2] : 0;

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function genderKey(?string $gender): string
    {
        return in_array($gender, ['boy', 'girl'], true) ? $gender : 'none';
    }

    private function money(int $value): string
    {
        return number_format($value, 0, ',', ' ') . ' ₽';
    }

    // ─── Черновик урока (users.telegram_state) ─────────────────

    /**
     * @return array<string, mixed>|null
     */
    private function wizard(User $user): ?array
    {
        $raw = (string) $user->telegram_state;

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $updatedAt = (int) ($decoded['ts'] ?? 0);

        if ($updatedAt > 0 && $updatedAt < time() - self::WIZARD_TTL_MINUTES * 60) {
            $this->clearWizard($user);

            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $wizard
     */
    private function saveWizard(User $user, array $wizard): void
    {
        $wizard['ts'] = time();
        $user->telegram_state = json_encode($wizard, JSON_UNESCAPED_UNICODE);
        $user->save();
    }

    private function clearWizard(User $user): void
    {
        if ((string) $user->telegram_state === '') {
            return;
        }

        $user->telegram_state = null;
        $user->save();
    }

    private function studentNameFromWizard(User $user): ?string
    {
        $wizard = $this->wizard($user);

        if ($wizard === null) {
            return null;
        }

        return $this->studentById($user, (int) ($wizard['student_id'] ?? 0))?->name;
    }

    /**
     * @param  array<string, mixed>|null  $wizard
     */
    private function subjectFromWizard(?array $wizard): ?Subject
    {
        $subjectId = (int) ($wizard['subject_id'] ?? 0);

        if ($subjectId <= 0) {
            return null;
        }

        return Subject::where('id', $subjectId)->where('is_deleted', 0)->first();
    }

    /**
     * Последняя цена урока ученика или цена по умолчанию — подсказка в мастере.
     */
    private function suggestedPrice(int $studentId): int
    {
        $last = Lesson::where('student_id', $studentId)
            ->where('is_deleted', 0)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->value('price');

        $price = (int) $last;

        return $price > 0 ? $price : (int) config('lesson.default_price', 3000);
    }

    /**
     * Параметры последнего урока ученика — основа для быстрого добавления.
     *
     * @return array{subject_id: int, price: int, duration: int, time: ?string}|null
     */
    private function lastLessonData(int $studentId): ?array
    {
        $lesson = Lesson::where('student_id', $studentId)
            ->where('is_deleted', 0)
            ->whereNotNull('subject_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if ($lesson === null) {
            return null;
        }

        $subjectExists = Subject::where('id', (int) $lesson->subject_id)
            ->where('is_deleted', 0)
            ->exists();

        if (! $subjectExists) {
            return null;
        }

        return [
            'subject_id' => (int) $lesson->subject_id,
            'price'      => (int) $lesson->price,
            'duration'   => (int) $lesson->duration,
            'time'       => $lesson->time ? substr((string) $lesson->time, 0, 5) : null,
        ];
    }

    /**
     * Комментарий к уроку: «-» очищает поле.
     */
    private function normalizeComment(string $text): string
    {
        $value = trim($text);

        return $value === '-' ? '' : mb_substr($value, 0, 500);
    }

    private function undoLesson(User $user, string $chatId, int $lessonId): void
    {
        $lesson = $this->ownedLesson($user, $lessonId);

        if ($lesson === null) {
            $this->sendMessage($chatId, lng('telegram.lesson_not_found'));

            return;
        }

        $lesson->is_deleted = 1;
        $lesson->save();

        UserCache::flush($user);

        $this->sendMessage($chatId, lng('telegram.lesson_undone'));
    }

    /**
     * Урок текущего пользователя (иначе null) — общая проверка владения для бота.
     */
    private function ownedLesson(User $user, int $lessonId): ?Lesson
    {
        return Lesson::where('id', $lessonId)
            ->where('is_deleted', 0)
            ->whereIn('student_id', $user->students()->select('students.id'))
            ->first();
    }

    /**
     * Подпись урока в списке финансов: дата и цена.
     */
    private function lessonLabel(Lesson $lesson): string
    {
        return ($lesson->date ? Carbon::parse($lesson->date)->format('d.m') : '—')
            . ' · ' . $this->money((int) $lesson->price);
    }

    private function token(): string
    {
        return (string) config('services.telegram_students.bot_token');
    }
}
