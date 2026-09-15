<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Lesson;
use App\Model\Student;
use App\Model\Subject;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Отдельный Telegram-бот для управления учениками: личный кабинет репетитора в чате.
 *
 * Читает и изменяет только данные владельца чата; привязка идёт по одноразовой
 * ссылке из настроек (таблица users: telegram_chat_id / telegram_link_code).
 */
final class StudentTelegramBotService
{
    private const API = 'https://api.telegram.org/bot';
    private const LINK_TTL_MINUTES = 15;
    private const UNPAID_LIMIT = 8;
    private const UPCOMING_LIMIT = 15;

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
        $this->dispatch($user, $chatId, $command, $args);
    }

    private function handleStart(string $chatId, string $args, ?string $username): void
    {
        $user = $this->userByChatId($chatId);

        if ($args === '') {
            if ($user === null) {
                $this->sendMessage($chatId, lng('telegram.not_linked'));

                return;
            }

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
            'rename'           => $this->renameStudent($user, $chatId, $args),
            'class'            => $this->classStudent($user, $chatId, $args),
            'desc'             => $this->describeStudent($user, $chatId, $args),
            'lesson'           => $this->addLesson($user, $chatId, $args),
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
                $label = ($lesson->date ? Carbon::parse($lesson->date)->format('d.m') : '—')
                    . ' · ' . $this->money((int) $lesson->price);

                $keyboard[] = [
                    ['text' => '✅ ' . $label, 'callback_data' => 'pay:' . $lesson->id],
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

    // ─── Запись ────────────────────────────────────────────────

    private function renameStudent(User $user, string $chatId, string $args): void
    {
        [$index, $value] = $this->splitIndex($args);
        $student = $this->studentByIndex($user, $index);

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        if ($value === '') {
            $this->sendMessage($chatId, lng('telegram.edit_usage', ['format' => '/rename <номер> <новое имя>']));

            return;
        }

        $student->name = $value;
        $student->save();

        $this->sendMessage($chatId, lng('telegram.rename_success', ['name' => $student->name]));
    }

    private function classStudent(User $user, string $chatId, string $args): void
    {
        [$index, $value] = $this->splitIndex($args);
        $student = $this->studentByIndex($user, $index);

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        if ($value === '') {
            $this->sendMessage($chatId, lng('telegram.edit_usage', ['format' => '/class <номер> <класс>']));

            return;
        }

        $student->class = $value;
        $student->save();

        $this->sendMessage($chatId, lng('telegram.class_success', [
            'class' => $student->current_class !== '' ? $student->current_class : $value,
        ]));
    }

    private function describeStudent(User $user, string $chatId, string $args): void
    {
        [$index, $value] = $this->splitIndex($args);
        $student = $this->studentByIndex($user, $index);

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        if ($value === '') {
            $this->sendMessage($chatId, lng('telegram.edit_usage', ['format' => '/desc <номер> <описание>']));

            return;
        }

        $student->description = $value;
        $student->save();

        $this->sendMessage($chatId, lng('telegram.desc_success'));
    }

    private function addLesson(User $user, string $chatId, string $args): void
    {
        $parts = preg_split('/\s+/u', trim($args)) ?: [];

        if (count($parts) < 5) {
            $this->sendMessage($chatId, lng('telegram.lesson_usage'));

            return;
        }

        $student = $this->studentByIndex($user, (string) array_shift($parts));

        if ($student === null) {
            $this->sendMessage($chatId, lng('telegram.student_not_found'));

            return;
        }

        $subjectQuery = array_shift($parts);
        $subject = $this->findSubject((string) $subjectQuery);

        if ($subject === null) {
            $this->sendMessage($chatId, lng('telegram.subject_not_found', [
                'subjects' => implode(', ', Subject::nameMap()),
            ]));

            return;
        }

        $date = $this->parseDate((string) array_shift($parts));

        if ($date === null) {
            $this->sendMessage($chatId, lng('telegram.date_invalid'));

            return;
        }

        $time = $this->parseTime((string) array_shift($parts));

        if ($time === null) {
            $this->sendMessage($chatId, lng('telegram.time_invalid'));

            return;
        }

        $priceRaw = (string) array_shift($parts);
        $price = (int) preg_replace('/\D/', '', $priceRaw);

        if ($price <= 0) {
            $this->sendMessage($chatId, lng('telegram.price_invalid'));

            return;
        }

        $duration = (int) preg_replace('/\D/', '', (string) ($parts[0] ?? ''));

        if ($duration <= 0) {
            $duration = (int) config('lesson.default_duration', 60);
        }

        if (! app(TariffService::class)->canUse($user, 'lessons')) {
            $this->sendMessage($chatId, lng('telegram.tariff_limit'));

            return;
        }

        $lesson = new Lesson([
            'subject_id' => $subject->id,
            'price'      => $price,
            'duration'   => $duration,
            'date'       => $date->toDateString(),
            'time'       => $time,
            'is_payed'   => 0,
            'is_future'  => $date->gte(Carbon::today()) ? 1 : 0,
            'comment'    => '',
            'is_deleted' => 0,
        ]);

        $student->lessons()->save($lesson);

        $this->sendMessage($chatId, lng('telegram.lesson_added', [
            'name'     => $student->name,
            'subject'  => $subject->localizedName(),
            'date'     => $date->format('d.m.Y'),
            'time'     => $time,
            'price'    => $this->money($price),
            'duration' => $duration,
        ]));
    }

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

    private function payLesson(User $user, string $chatId, int $lessonId, string $callbackId, ?int $messageId): void
    {
        $lesson = Lesson::where('id', $lessonId)
            ->where('is_deleted', 0)
            ->whereIn('student_id', $user->students()->select('students.id'))
            ->first();

        if ($lesson === null) {
            $this->answerCallback($callbackId, lng('telegram.lesson_not_found'));

            return;
        }

        $lesson->is_payed = $lesson->is_payed ? 0 : 1;
        $lesson->date_payed = $lesson->is_payed ? Carbon::now() : null;
        $lesson->save();

        $date = $lesson->date ? Carbon::parse($lesson->date)->format('d.m.Y') : '';

        $this->answerCallback($callbackId, lng(
            $lesson->is_payed ? 'telegram.lesson_paid' : 'telegram.lesson_unpaid',
            ['date' => $date],
        ));

        $student = $this->studentById($user, (int) $lesson->student_id);

        if ($student !== null) {
            $this->finance($user, $chatId, $student, $messageId);
        }
    }

    // ─── Telegram API ──────────────────────────────────────────

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
     * @return Collection<int, Student>
     */
    private function activeStudents(User $user): Collection
    {
        return $user->students()
            ->where('is_deleted', 0)
            ->orderBy('name')
            ->get();
    }

    private function studentById(User $user, int $studentId): ?Student
    {
        return $user->students()->where('students.id', $studentId)->first();
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
     * @return array{0: string, 1: string}  команда без слэша и остаток строки
     */
    private function parseCommand(string $text): array
    {
        if ($text === '') {
            return ['', ''];
        }

        $withoutSlash = str_starts_with($text, '/') ? mb_substr($text, 1) : $text;
        $parts = preg_split('/\s+/u', trim($withoutSlash)) ?: [];
        $command = mb_strtolower((string) array_shift($parts));

        // /help@bot_name → help
        $command = explode('@', $command)[0];

        return [$command, implode(' ', $parts)];
    }

    /**
     * @return array{0: string, 1: string}  первый токен и остаток
     */
    private function splitIndex(string $args): array
    {
        $parts = preg_split('/\s+/u', trim($args), 2) ?: [];

        return [(string) ($parts[0] ?? ''), trim((string) ($parts[1] ?? ''))];
    }

    private function findSubject(string $query): ?Subject
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return null;
        }

        $matches = Subject::active()->filter(function (Subject $subject) use ($needle): bool {
            $names = is_array($subject->name) ? $subject->name : [];
            $haystack = array_merge(
                [(string) $subject->code, (string) $subject->slug],
                array_map('strval', array_values($names)),
            );

            foreach ($haystack as $value) {
                if ($value !== '' && mb_strpos(mb_strtolower($value), $needle) !== false) {
                    return true;
                }
            }

            return false;
        });

        return $matches->count() === 1 ? $matches->first() : null;
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

    private function token(): string
    {
        return (string) config('services.telegram_students.bot_token');
    }
}
