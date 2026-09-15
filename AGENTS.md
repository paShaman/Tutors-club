# AGENTS.md — Tutors Club Cabinet

Guidance for AI coding agents working in this repository. Read this file before making changes.

---

## 1. Project overview

**Tutors Club Cabinet** is the main product of the Tutors-club project (there is no separate landing/marketing app yet). It is a personal web cabinet for private tutors where they can:

- manage their **students** (add, edit, soft-delete, avatars, dynamic school class);
- plan and track **lessons** (FullCalendar schedule, price, duration, paid/future status);
- manage a **topic planner** (topics/subtopics by subject, per-student status and mastery date, review log with last-review date);
- watch **statistics** on the dashboard (weekly workload chart, monthly earnings);
- manage their **profile** (name, avatar with client-side cropping, password, linked social accounts, linking the Telegram bot);
- get **automated reminders and quick lesson management through a personal Telegram bot** (see §7).

The user-facing UI is Russian; backend locale is `ru` (with `en` translation files that must be kept in sync for backend strings, see §7).

Monetization: there are three **tariff plans** in `config/tariffs.php` — free "Free" (full functionality, 1 student), promo "Promo" (10 students, used for campaigns) and paid "Paid" (100 ₽/month or 1000 ₽/year, unlimited). Payments and self-service plan switching are **not implemented**; an administrator assigns and cancels plans (roles too) in the admin section under `/admin/users` (see §5).

## 2. Non-negotiable rules (read first)

- **Git is completely off-limits.** Do not stage, commit, push, branch, reset, stash, merge, rebase, or craft commit messages. Do not even run `git log`/`git status`/`git diff` unless the user explicitly asks. Version control is done by the user only.
- **Never touch `.env`, and never log/print secrets** (DB credentials, `APP_KEY`, `VK_ID_*`, `YANDEX_*`, `SMARTCAPTCHA_*`, `TELEGRAM_*`, `MAX_*`). There is no `.env.example` in the repo.
- **There is no access to production.** The app runs on a shared host (docroot = `public_html`) with MySQL. Do not run any artisan/CLI command against production, and do not assume production has artisan/Node available.
- **Never run `php artisan migrate` / `db:seed` / config or cache commands** — even locally — without an explicit user request. Propose the migration/change and let the user apply it.
- **Do not install new Composer or npm packages** without asking first. Note: `axios`, `qs` and `lodash-es` are **not** dependencies anymore — Inertia v3 dropped them in favour of a built-in XHR client and `es-toolkit`. Never import them; for a standalone request that must not trigger a page visit use `useHttp` from `@inertiajs/vue3` (see §7).
- **Do not write tests.** There is no `tests/` directory, no test script, and tests are intentionally not part of the workflow yet. Do not scaffold PHPUnit/Pest/component-test infrastructure.
- **The UI-kit is the source of truth for styling.** Before writing any CSS/markup, creating a component, or restyling a page, consult the admin-only reference page at `/admin/uikit` (controller `UiKitController`, source `resources/js/Pages/UiKit.vue`, helpers in `resources/js/components/uikit/*`). It documents every `components/ui/*` + `components/popups/*` component, the `@theme` tokens, form-field classes, toasts and popups. Reuse them instead of inventing new styles; when you add or change a UI primitive, extend the UI-kit page in the same change.
- **Form controls and icon actions are mandatory components — do not use native ones.** Use `components/ui/Select.vue` (never a raw `<select>`), `Checkbox.vue` (never `<input type="checkbox">`), `Radio.vue` (never `<input type="radio">`) and `IconButton.vue` for compact icon actions (edit/delete/restore/show). Only `field`-styled `input`/`textarea` (text, number, date, time, search) stay native.
- **Do not refactor legacy code** (`app/Model`, `App\Common`, `App\Notification`, `App\Form`, `App\Image`, old controllers/models) unless the task explicitly requires it. Match the surrounding file's style when you do edit one.
- **The changelog is not hand-edited mid-task** — it lives in `ChangelogController` and is updated once at the end of a session that produced user-facing changes (see §10).
- **Billing/tariffs are not self-service for users.** Do not implement payment flows or user-facing plan switching; an administrator assigns/cancels subscriptions in the admin section under `/admin/users` (see §5). Limits come from `config/tariffs.php` only; the effective plan is the latest active row in `user_subscriptions` (or `free`). Do not use the legacy `users.account` column or the empty `users_payments` table.
- **Caching is mandatory for hot reads (Redis).** `CACHE_DRIVER=redis` and `SESSION_DRIVER=redis` (predis or phpredis); Redis db 0 holds sessions, db 1 holds the cache. Every cache key goes through `app/Support/CacheKeys.php` — never inline a key string in a controller/model. Personal keys must contain the user/student id. Use `rememberForever` + explicit `forget` only where a code hook exists; where it does not (or where data changes by time alone), use a short TTL or the per-user data version (`CacheKeys::dataVersion()` / `bumpData()`), which invalidates the user's dashboard, lessons, calendar, student card and topic trees. Never cache `flash`, `auth.user`, CSRF data, CAPTCHA/OAuth tokens or whole Inertia responses. Invalidate through `UserCache::flush($user)` (`app/Support/UserCache.php`) — it bumps the version and clears the tariff counters, and is the one entry point shared by controllers and the Telegram bot.
- **Do not add new `window.location.reload()` calls** and prefer migrating touched flows away from full reloads (see §7).

## 3. Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP `^8.5`, Laravel `^13.0` (declarative `bootstrap/app.php`), Inertia Laravel v3 |
| Frontend | Vue 3 (`<script setup>`), TypeScript, Inertia Vue v3 (+ `@inertiajs/vite`) |
| Build | Vite (`laravel-vite-plugin`, `@vitejs/plugin-vue`, `@inertiajs/vite`, `@tailwindcss/vite`), output goes to `public_html/build` (gitignored) |
| Styling | Tailwind CSS v4 with `@theme` design tokens defined in `resources/css/app.css` (shadcn-like tokens, glass utilities); fonts Geist/Inter |
| UI kit | Local `resources/js/components/ui/*` built on radix-vue, `class-variance-authority`, `clsx`, `tailwind-merge`, `cn()` from `@/lib/utils`; icons from `lucide-vue-next` |
| Widgets | `@fullcalendar/*` (calendar), `chart.js` + `vue-chartjs` (dashboard charts) |
| Auth | Email/password, **VK ID** (`@vkid/sdk`, config `services.vkid`, OAuth redirect-flow), **Yandex ID** OAuth (`services.yandex`), Yandex SmartCaptcha on registration, signed auto-login URL |
| Bots | `FeedbackBotService` — relays feedback to the owner in Telegram **or** MAX (primary bot from `FEEDBACK_PRIMARY_BOT`); `StudentTelegramBotService` — personal Telegram bot for lessons/payments/debts (webhook, plain Laravel `Http`, no packages) |
| Routing on client | No Ziggy — frontend uses **literal URL strings** (e.g. `/students/edit`), not `route()` |
| Tests | none |
| Lint/format | none configured (no ESLint/Prettier/Pint/PHPStan) |

Path alias: `@/*` → `resources/js/*` (configured in both `vite.config.ts` and `tsconfig.json`).

## 4. Repository map

```
app/
├── Common.php                 # legacy helpers (pagination etc.)
├── Form.php                   # builds modal/form definitions passed as Inertia props
├── Image.php                  # avatar storage/cropping/cleanup helpers
├── Notification.php           # legacy session flash helpers
├── helpers.php                # lng() localization helper
├── Http/
│   ├── Controllers/           # Auth, Avatar, Calendar, Changelog, Dashboard, Lesson,
│   │                          # Page, Planning, Student, UiKit, User, ControllerHelper,
│   │                          # Subject, Promo, AdminUser, Feedback, Telegram (link/unlink),
│   │                          # StudentBot (Telegram webhook)
│   └── Middleware/            # Authenticate, EnsureUserIsAdmin (alias 'admin'),
│                              # HandleInertiaRequests, SetLocale
├── Model/                     # LEGACY Eloquent namespace: User, Role, Student, Lesson,
│                              # Topic, StudentTopic, TopicReview, UserSubscription, Page,
│                              # Subject, PromoBanner  (NOT App\Models)
├── Services/                  # SocialAccountService, SocialOAuthProvider,
│                              # VkIdService, YandexIdService, TariffService,
│                              # FeedbackBotService (Telegram + MAX), StudentTelegramBotService
├── Support/                   # CacheKeys — единый реестр ключей кэша; UserCache — инвалидация
└── Providers/
routes/
├── web.php                    # THE single routing file (all HTTP routes)
└── api.php                    # present but unused — no routes in use go through it
config/
├── services.php               # vkid / yandex / yandex_smartcaptcha / telegram / telegram_students / max / feedback / yandex_metrika credentials
├── agreements.php             # registration agreement documents (shared via Inertia)
├── company.php                # legal requisites (shared via Inertia)
├── locales.php                # available UI languages (code => label) — one entry per lang directory
├── lesson.php                 # LESSON_DEFAULT_PRICE (3000), LESSON_DEFAULT_DURATION (60)
└── tariffs.php                # tariff plans: limits + prices (free/promo/paid)
resources/
├── css/app.css                # Tailwind v4 entry: @theme tokens + global styles
├── lang/{ru,en}/              # messages.php (backend + ui.* strings); admin.php (admin-only
│                              # strings, shipped only to admins); validation.php (one per locale)
├── views/app.blade.php        # single Blade shell (@vite, @inertiaHead, @inertia) + стартовая заставка #app-splash
└── js/
    ├── app.ts                 # createInertiaApp entry (pages: './Pages' — resolver генерирует @inertiajs/vite);
    │                          # mounts <Toaster/> globally next to <App>, снимает заставку после монтирования
    ├── Pages/                 # one .vue per route component: Dashboard, Students, StudentDetail,
    │                          # Lessons, Calendar, Planning, Settings, Login, Register, Error
    │                          # (+ Promo, Subjects, Users, UiKit — admin-only pages under /admin/*)
    ├── Layouts/AppLayout.vue  # sidebar + layout; pages opt in via defineOptions
    ├── components/
    │   ├── ui/                # Button + IconButton, Checkbox, Radio, Select, Tabs, Card,
    │   │                      # CardHeader, CardTitle, Table + Table* parts, UserAvatar,
    │   │                      # AvatarPicker, ImageCropper, TopicStatusBadge, Toaster + ToastItem
    │   ├── popups/            # ConfirmDialog, ChangelogModal, RequisitesModal, StudentFormPopup,
    │   │                      # LessonFormPopup, TopicFormPopup, ReviewFormPopup,
    │   │                      # SubjectFormPopup, PromoFormPopup, AdminSubscriptionPopup
    │   ├── uikit/             # UiKitSection + CodeBlock (helpers for the /admin/uikit page)
    │   ├── icons/             # TelegramIcon, MaxIcon (мессенджеры обратной связи)
    │   └── social/            # SocialAuth, VkIdAuth, YandexAuth, VkIcon, YandexIcon, TelegramIcon
    ├── lib/                   # i18n.ts (useI18n), utils.ts (cn), toast.ts (useToast),
    │                          # social.ts, scrollLock.ts, studentColors.ts,
    │                          # iconDraw.ts, useMediaQuery.ts
    └── types/index.ts         # SharedProps + InertiaConfig.sharedPageProps augmentation
public_html/                   # web root (Laravel public dir via usePublicPath)
```

## 5. Backend conventions (PHP)

- **Routing**: everything lives in `routes/web.php`. Page endpoints render Inertia; student/lesson/planning mutations are Inertia POST routes that `redirect()->back()` (see §7). Route names use dot notation (`auth.yandex`, `lessons`, `planning`).
- **Controllers** live in `App\Http\Controllers`. Page methods return `Inertia::render('PageName', props)`. Student/lesson/planning mutation methods return `Illuminate\Http\RedirectResponse` via `back()->with('success'|'error', lng(...))`, or `back()->withErrors($validator)->withInput()` on validation failure. `ControllerHelper::resultSuccess()` / `resultError()` (`{ success, data }`) remain only for the intentional JSON endpoints (`/avatar/upload`, `/changelog`).
- **Models** use the legacy namespace `App\Model` (singular). Keep using it for new models. Relations: `User` ↔ `Student` (many-to-many via `students_to_users`), `User` has many `Topic` and `UserSubscription`; `Student` has many `Lesson`, `StudentTopic` (per-student topic status) and `TopicReview` (review log); `Topic` and `Lesson` both belong to `Subject` via `subject_id` (the old `lessons.subject` code column is gone — read codes via `Subject::codeMap()`). `Student` and `Subject` generate a unique `slug` on save (`Student::makeSlug()`), and pretty URLs bind by it (`{student:slug}`, `{subject:slug}`).
- **Localization**: user-facing backend strings MUST go through the `lng('...')` helper (dot key under `messages.php`), e.g. `lng('success.add_student')`. **Every new key must be added to BOTH `resources/lang/ru/messages.php` and `resources/lang/en/messages.php`.** Group keys under `success.*`, `error.*`, `title`, etc. See existing usage in controllers. Validation texts and field names (`attributes`) live in `resources/lang/<locale>/validation.php` — keep a file for **every** locale listed in `config/locales.php`, not just the primary one. **Admin-only strings live in `resources/lang/{ru,en}/admin.php`** (same nested shape: `ui.uikit.*`, `ui.users.*`, `ui.subjects.*`, admin `ui.promo.*`, `error.add_subject`, `success.admin_*`); `lng()` falls back to that file and it is never sent to regular users (see §6/§7).
- **Language-file typography (неразрывные пробелы)**: неразрывный пробел — часть самой строки в `resources/lang/**`; ставьте **литеральный символ U+00A0** прямо в значение. Никогда не `&nbsp;` (клиентские строки приходят в Vue как plain text и сущность отрендерится буквально) и никогда не через JS/runtime-типографику. Правила повторяют набор `nbsp` из Typograf (данные `src/data/ru.ts` / `en-US.ts`) и применяются к `messages.php` и `validation.php` обеих локалей: NBSP после любого слова из 1–2 букв и после слова из списка локали — ru `а|без|в|во|если|да|до|для|за|и|или|из|к|ко|как|ли|на|но|не|ни|о|об|обо|от|по|про|при|под|с|со|то|у`, en `a|an|and|as|at|bar|but|by|for|if|in|nor|not|of|off|on|or|out|per|pro|so|the|to|up|via|yet`; NBSP перед частицами `ли|ль|же|ж|бы|б` (только ru); NBSP между словом и завершающим коротким словом (1–3 буквы с `.`/`!`/`?`/`…`). Внутри HTML-тегов и `:placeholder` пробелы не трогаем. В ru-списке набор букв кириллический, поэтому латинские «VK ID», «Telegram» не склеиваются.
- **Roles (admin)**: `roles`/`roles_to_users` power a minimal role system: `App\Model\Role` (with `Role::ADMIN = 'admin'`), `User::roles()` / `User::hasRole($code)` / `User::isAdmin()`. **Admin-only pages live under `/admin/*`** and use the `admin` middleware alias (`App\Http\Middleware\EnsureUserIsAdmin`, registered in `bootstrap/app.php`), which renders the Inertia `Error` page with status **403** for non-admins. Roles are assigned by an administrator on `/admin/users` (`POST /admin/users/roles`, checkbox per role); an admin cannot remove their own `admin` role, and roles are not edited directly in the DB anymore. `User::roleTitles()` caches the role list (30 min) and `User::flushRoleCache($id)` clears it. The shared prop `auth.user.is_admin` drives admin-only sidebar links (`AppLayout`). The old `App\Access` constants no longer exist.
- **Tariffs (планы)**: limits live only in `config/tariffs.php` (`plans.<plan>.limits.<feature>`). Feature registry: `students`, `lessons`, `topics` — quotas (`null` = unlimited); `subjects` (предметы) now live in the `subjects` table (`App\Model\Subject`), not in code — read codes via `Subject::codes()` and display names via `Subject::nameMap()` (name is a per-locale JSON map). The effective plan = latest active row in `user_subscriptions` (not expired) or `free`; an expired paid plan auto-falls back to `free` and surfaces a notice (`TariffService::expired()`). Enforce quotas only on **creation** via `TariffService::canUse()` (edit/delete/restore don't consume quota); a new creatable entity must add its counter to `TariffService::used()` and a guard at its creation point. The single student-creation path is `StudentController::editStudent()` (plus the `User::addStudent()` safety net). Subscriptions are assigned/cancelled on `/admin/users` (`TariffService::assign()` / `cancel()`, `POST /admin/users/subscription[/cancel]`), and those methods flush the user's tariff cache.
- **PHP style for NEW files**: add `declare(strict_types=1);`, typed signatures/return types, `final class` where sensible, aligned multi-line arrays, no noisy PHPDoc — write short Russian comments only where they explain "why". Do not retro-edit old files to this style.
- Middleware: `auth`, `guest`, `signed` (auto-login link), `admin` (alias of `EnsureUserIsAdmin`). Shared Inertia props are assembled in `app/Http/Middleware/HandleInertiaRequests.php` (`auth.user`, `flash`, `social`, `agreements`, `requisites`, `feedback`, `tariff`, `promoBanners`, `locale`, `locales`) plus the once-props for translations — when you add globally shared data, extend this class AND `resources/js/types/index.ts` (`SharedProps`).

## 6. Frontend conventions (Vue/TS)

- A **page** = `resources/js/Pages/<Name>.vue`, referenced by the controller's `Inertia::render('<Name>', ...)`. Layout opt-in: `defineOptions({ layout: AppLayout })`. Set page title with `<Head :title="t('ui....')"/>`.
- **Inertia v3 specifics.** The server adapter (`inertiajs/inertia-laravel`) and the client adapter (`@inertiajs/vue3`) must always be bumped **together** — they share the initial-page payload format (a `<script type="application/json" data-page="app">` element, no longer a `data-page` attribute). Blade `<head>` elements that Inertia should manage need the `data-inertia` attribute (the v2 `inertia` attribute is ignored, which produces duplicate `<title>`/`<meta>`); rendering the root view has not changed otherwise (`@inertia`/`@inertiaHead` rows still work, `resources/views/app.blade.php`). Shared-prop typing is declared in `resources/js/types/index.ts` as `declare module '@inertiajs/core' { interface InertiaConfig { sharedPageProps: SharedProps } }` — `@inertiajs/vue3` no longer exports `PageProps`. The `config/inertia.php` keys live under `pages.*` (`paths`, `extensions`, `ensure_pages_exist`).
- **Page resolution is generated, not hand-written.** `resources/js/app.ts` passes `pages: './Pages'` and the `@inertiajs/vite` plugin (see `vite.config.ts`, `ssr: false`) rewrites it into a lazy `import.meta.glob` resolver — pages are code-split per route. Adding a page needs no registration anywhere; just create `Pages/<Name>.vue`. Do not re-add a manual `resolve`/eager glob (it puts every page into the entry bundle).
- **Standalone requests use `useHttp`** (`@inertiajs/vue3`): it builds multipart for `File`/`Blob`, adds CSRF/`X-Requested-With` itself, and exposes `processing`/`errors` plus lifecycle callbacks. Never hand-roll `fetch` + `XSRF-TOKEN` parsing, and never import `axios`/`qs`/`lodash-es` (none are installed).
- **Optimistic updates**: `router.post(url, payload, { optimistic: (props) => … })`, or `.optimistic()` on `useForm`/`useHttp`. The callback returns only the changed top-level props; Inertia rolls them back automatically on 4xx/5xx. When the change touches server-computed aggregates (e.g. `Lessons` sums/counters), mirror the controller's arithmetic exactly, or the UI will contradict the response.
- **Instant visits** (`component` on `Link`/`router.visit`, plus `pageProps` placeholders) only help when the target component differs — e.g. `/students/{slug}` → `StudentDetail`; same-component filter links gain nothing. The target must render from shared props alone, so pass `pageProps` in the exact shape it expects (`Students.vue` → `instantStudentProps`).
- Use `<script setup lang="ts">`. Type props with `defineProps<{...}>()`; typing is **pragmatic** — an occasional local `any` is acceptable, but keep shared/global types in `types/index.ts`.
- Import with the `@/` alias for `Layouts/`, `components/`, `lib/`; use relative paths within a directory.
- **Reuse existing UI**, don't restyle components: `components/ui/*` (Button + `IconButton`, Checkbox, Radio, Select, Card/CardHeader/CardTitle, UserAvatar, Toaster), `components/popups/*` (ConfirmDialog, form popups). Buttons are `Button` (compact icon actions — `IconButton`); forms live in popups bound to local refs. **Check the UI-kit reference page `/admin/uikit` (`Pages/UiKit.vue` + `components/uikit/*`) before styling anything** and keep it up to date when a component/token changes. The subtle hover **lift is reserved for `IconButton`** — regular `Button` does not lift; disable it on a specific icon button with `:lift="false"` (e.g. inside a highlighted row where it would stick out).
- **Numeric stat tiles are `components/ui/StatCard.vue` — never hand-built.** Any "плашка со статистикой" (label + big number + round icon badge, e.g. on the dashboard and the student card) is rendered with `<StatCard>`, so the number *always* counts up from zero on appearance (and from the previous value on data updates) — do not reimplement the markup or wire `useCountUp` (`lib/useCountUp.ts`) manually. Props: `label` (already translated), `value: number`, optional `icon` (a lucide component) or an `#icon` slot, `tone` (`primary`/`emerald`/`blue`/`red`/`amber`/`violet` — badge + icon colour), `format` (`number`/`money`/`percent`), `decimals` (default 0), `valueClass`, `hint` (small caption under the number) and `class`. Adding such a tile anywhere means using this component; a new tone is added to its `tones` map, not inline on the page.
- **Never use native form controls.** `Select.vue`, `Checkbox.vue` and `Radio.vue` are the only allowed choice/toggle controls; native `input`/`textarea` keep the `field` class. `Select` is generic over its value (`generic="T extends SelectValue"`, options typed as `SelectOption<T>[]`), renders a custom listbox on desktop (`min-width: 1024px`, keyboard + click-outside) and the system `<select>` on mobile; it accepts `required` (browser validation on both breakpoints), `id`, `name`, `ariaLabel`, `disabled` and `placeholder`.
- **Nested popups** (a popup opening another popup on top): the inner popup must not add a second dark `bg-black/40 backdrop-blur` layer, otherwise the background is dimmed twice. Add a `nested` flag to the reused popup so its own overlay uses `z-80` and its modal `z-90`, and hide the parent's backdrop while the child is open (`v-if="show && !childOpen"`). This keeps a single dimming layer with the parent window pushed back — see `TopicFormPopup` opened from `LessonFormPopup`.
- **User feedback = toasts.** Use `useToast()` from `lib/toast.ts` (`toast.success/error/warning/info`) instead of inline flash banners or a blocking alert modal. The global `<Toaster/>` (mounted in `app.ts`, so it also works on auth pages without `AppLayout`) renders the stack bottom-right and auto-surfaces Inertia `flash.success`/`flash.error` — do not re-add per-page flash markup. Success/info auto-dismiss after 4 s, warning after 6 s, **error stays until closed manually** (never auto-hide errors). Form **field** validation errors stay inline next to the input (`form.errors.*`); the `onError` toast is a fallback channel.
- **Tariff prop**: the shared `tariff` prop (`TariffInfo` in `types/index.ts`) carries `plan`, `limits`, `usage` and `can.<feature>` flags. Gate "add" buttons on `tariff.can.*` and explain the block with `toast.warning(t('ui.tariff.limit.<feature>'))`; the server stays the source of truth. When `tariff.expired` is true, `AppLayout` shows a notice.
- **Loading feedback lives in three places, never in a page-level reload.** 1) The startup splash `#app-splash` is rendered by `app.blade.php` with inline styles and removed by `app.ts` after the app mounts (it appears only after ~250 ms, so fast loads don't flash). 2) Inertia's progress bar colour is the brand token — keep `progress: { color: 'hsl(252 87% 67%)' }` in `app.ts` in sync with `--color-primary`. 3) Long server round-trips that leave the old DOM on screen (lesson filter changes, `FullCalendar` event fetches) show a local busy state: dim the stale block, add `delay-200` so quick answers don't blink, and set `aria-busy`. Do not add new spinners outside these patterns and never reload the page to show progress.
- Styling: Tailwind utility classes with the design-token palette from `resources/css/app.css` (`bg-background`, `text-foreground`, `bg-primary/...`, `border-border`, tokens like `--color-primary: hsl(252 87% 67%)`, radius 0.75rem). Do not introduce ad-hoc hex colors; extend the `@theme` block if a token is genuinely needed.
- **Page headers**: use the `page-header` / `page-header-actions` utilities from `resources/css/app.css` for any "title + action buttons" header. On desktop they keep the title and actions on one row (the action block never wraps under the title); on phones the blocks stack and the actions may wrap. Never hand-roll a bare `flex items-start justify-between gap-4` header — the action block squeezes the title into a thin column. Do not add `shrink-0` to the action block manually; the utilities handle it. On narrow screens hide button labels and keep the icons only (`<span class="hidden sm:inline">…</span>` + `:title`) so the actions stay compact. Documented on `/admin/uikit`.
- Recurring visual bugs found in review must be fixed once and then locked in: add/extend a shared utility in `resources/css/app.css`, document it on `/admin/uikit`, and state the rule here, instead of only patching the single page.
- Icons: `lucide-vue-next` (every icon used in a template must be imported — an unresolved PascalCase tag renders nothing and only warns in the console). Charts: register ChartJS modules where used.
- **Calendar = FullCalendar v7** (`@fullcalendar/vue3` + `temporal-polyfill`; plugins/theme come from entrypoints — `@fullcalendar/vue3/daygrid`, `/timegrid`, `/interaction`, `/themes/classic`, locales from `/locales/<code>`). v7 does not bundle CSS: `resources/css/fullcalendar.css` (imported by `Pages/Calendar.vue`) pulls in `skeleton.css` + `classic/palette.css` + `classic/theme.css` with `@import` and is the only place for project overrides (it recolours the theme through `--fc-classic-*` on `.fc-theme-custom`). v7's DOM has no readable `fc-*` classes, so element classes are passed via className options (`eventClass`, `dayCellTopInnerClass`, `slotHeaderInnerClass`, …) — they merge with the theme's own classes, so never style raw FullCalendar markup from a page. Events carry no colours from the backend: status colours live on `cal-event--unpaid` / `cal-event--future` in `fullcalendar.css`, and the feed puts only data into `extendedProps`. **Never put a dynamic class binding on the `<FullCalendar>` element** (e.g. `:class="isLoading && 'opacity-60'"`): Vue rewrites the element's whole `class` attribute and wipes the `fc-*` classes the library adds to its own root imperatively (`setClassName` never re-adds them), after which v7's flex layout collapses and the grid body disappears. Put dynamic classes (loading dimming, transitions) on a wrapper element and keep the calendar's own classes static.
- Vue components are single-file with `lang="ts"`; no CSS-in-JS. Shared helpers: `cn()` in `lib/utils.ts`, `useMediaQuery()` in `lib/useMediaQuery.ts`.
- **UI is multilingual — no hardcoded Russian in templates.** All user-facing client strings live under the `ui.*` namespace in `resources/lang/{ru,en}/messages.php` (same file as backend strings) and are rendered with `useI18n()` from `lib/i18n.ts`: `const { t, tp, locale, intlLocale } = useI18n()`, then `t('ui.x.y')` (interpolation: `t('ui.x.y', { name })`) or `tp('ui.x.count', n)` for plurals (`Intl.PluralRules`, forms `one`/`few`/`many`/`other`). Never hardcode Russian in `.vue`/`.ts`. Client strings keep their non-breaking spaces from the lang files (see §5) — no runtime typography. Format dates/numbers with `intlLocale` (not `'ru-RU'`). Adding a language = one entry in `config/locales.php` + a `resources/lang/<code>/` catalog (client translations are shared automatically via the `translations` Inertia prop). The backend applies the user's `users.locale` in `App\Http\Middleware\SetLocale`; read it via `lng()` server-side and `t()` client-side. The hardcoded changelog content itself (`ChangelogController`) stays Russian unless explicitly requested. **Admin-only UI strings live in `resources/lang/{ru,en}/admin.php`**; `t()` resolves them through the admin-only `adminTranslations` prop as a fallback after `translations`, so regular users never load them and admin pages need no special imports.

## 7. Request/data flows

**How pages get data.**
- Page GET endpoints (e.g. `GET /students`, `GET /lessons`, `GET /calendar`, `GET /planning`, `GET /`) return an Inertia page with props computed server-side.
- Navigation and list **filtering** use Inertia with pretty slug URLs (no query params): `/students/{student-slug}`, `/lessons/students/{student-slug}`, `/lessons/subjects/{subject-slug}`, `/lessons/students/{student-slug}/subjects/{subject-slug}`, `/planning/{subject-slug}` — `<Link>` or `router.get(url)` (server re-renders the page with filtered props; models bind by slug via `{model:slug}`).
- **Mutations are Inertia-native (post → redirect → get).** Student/lesson/planning create/edit/delete/pay/status/review submit with `router.post('/students/edit', payload, { preserveScroll: true, onSuccess, onError })`; the controller returns `back()->with('success'|'error', lng(...))`, or on validation failure `back()->withErrors($validator)->withInput()`. The resulting Inertia visit refreshes the page props (`students`, `sortedLessons`, …) and shows the success/error **toast** (via the global `Toaster`, see §6) **without any browser reload**. `router.post` defaults to `preserveState: true`, so local component state (expanded groups, FullCalendar view) is preserved. Inertia handles CSRF automatically — no manual `X-XSRF-TOKEN`/`_token`. `payLesson` is a toggle and returns a bare `back()` (no flash).
- **Form payload types must be `type` aliases, not `interface`** (e.g. `LessonFormData`/`StudentFormData` in `components/popups/*`); TS interfaces lack the implicit index signature Inertia's `RequestPayload` (`Record<string, FormDataConvertible>`) requires. The same applies to page-props types passed to `usePage<...>()` — they must satisfy `PageProps`, i.e. carry an index signature (`PageProps = { [key: string]: unknown }`).

**Remaining JSON endpoints (intentional).**
- `GET /calendar/events` — FullCalendar's events feed (`CalendarController::getEvents`); after a lesson mutation the calendar calls `calendarApi.refetchEvents()`.
- `POST /avatar/upload` — `AvatarPicker.vue` posts it with `useHttp`; multipart, CSRF and `X-Requested-With` come from the client. The endpoint answers **200 even for validation failures** (`ControllerHelper` `{ success, data }`), so the failure branch is parsed inside `onSuccess`, while `onHttpException` (419 and other non-2xx) and `onNetworkError` are handled separately.
- `GET /changelog` — hardcoded changelog array (see §10), fetched with `useHttp` in `ChangelogModal.vue`.
- `POST /telegram/webhook`, `POST /max/webhook`, `POST /telegram/students/webhook` — bot webhooks (`FeedbackController`, `StudentBotController`). No `auth` and **no CSRF** (exempted in `bootstrap/app.php`); each verifies its own secret header (`X-Telegram-Bot-Api-Secret-Token`, `X-Max-Bot-Api-Secret`) and answers `200` even on failure so the messenger does not retry forever. Register the webhook manually (there is no production access) — see the PHPDoc above each controller method.

**Telegram/MAX bots.**
- `StudentTelegramBotService` is the personal tutor bot: users link it from `/settings` (`POST /user/telegram/refresh` re-issues the one-time deep-link, `/user/telegram/unlink` detaches). Linking is stored in `users.telegram_chat_id` / `telegram_link_code[_expires_at]`; the multi-step lesson wizard draft lives in `users.telegram_state` (JSON, TTL 30 min). Commands: `/students`, `/student`, `/finance`, `/lessons`, `/debts`, `/lesson`, `/help`. It writes to the same tables as the cabinet, so every mutation calls `UserCache::flush($user)`, and it checks ownership itself (`payLesson`, `undoLesson`, `studentById`).
- Bot strings live under the `telegram.*` key in `resources/lang/{ru,en}/messages.php` (localized with `lng()` after `applyLocale($user)`).
- `FeedbackBotService` delivers cabinet feedback to the owner in the primary bot (`FEEDBACK_PRIMARY_BOT`) or the configured fallback, and relays messages written to the other messenger.

**Rules.**
- Do **not** add new `fetch()` JSON endpoints or new `window.location.reload()` calls; keep mutations Inertia-native.
- **Never trust an id from the request.** Mutations check that the entity belongs to the current user (`LessonController::ownsLesson()`, `$user->students()->where('students.id', …)`, `PlanningController::ownsTopic()`) and answer `lng('error.no_access')` otherwise; a foreign lesson id must not be editable, payable or deletable.
- Server responses for user-facing messages come localized via `lng()`; keep `success.*` / `error.*` keys synced in ru + en.
- Backend `back()->with('success'|'error', lng(...))` lands in the `flash` prop and is shown by the global `Toaster`; client-side Inertia `onError` uses `toast.error(...)` from `useToast()`. Do **not** render ad-hoc inline flash banners.
- **Cache invalidation rides on mutations.** Call `$this->flushUserCache($userId)` (protected helper in the base `Controller`) from every student/lesson/topic/status/review mutation — it bumps the user's data version and clears the tariff usage counters. The matching admin controllers clear their own keys: `Subject::flushCache()`, `PromoBanner::flushCache()`, `User::flushRoleCache($id)`, and `TariffService::assign()` / `cancel()` flush the tariff plan. Non-web writers (the Telegram bot `StudentTelegramBotService` — it creates lessons, toggles payments and undoes lessons) call `App\Support\UserCache::flush($user)`, the same helper the base controller delegates to.
- **Translations are Inertia once-props.** `HandleInertiaRequests::shareOnce()` ships `translations` (`messages.php`) — and, for admins only, `adminTranslations` (`admin.php`) — once per client session; Inertia then reuses them from memory instead of re-sending the whole catalog on every visit. The once key embeds the locale, an `admin|base` variant and the lang-file signature, and a session-stored `locale|admin` variant forces a resend after a language/role change or a deploy. Never move translations back into `share()`. New admin strings belong in `admin.php`, everything else in `messages.php`.

**Social/auth flows.**
- Registration: agreement consent (props `agreements` from `config/agreements.php`) + Yandex SmartCaptcha.
- VK ID: `components/social/VkIdAuth.vue` posts to `/auth/vk` (register/login) or `/user/socials/link` (link) with the VK token; `router.post` is the correct mechanism.
- Yandex: OAuth redirect via `AuthController::yandex*` routes; linking via `/user/socials/link/yandex`.
- Unlink: `Pages/Settings.vue` already calls `router.post('/user/socials/unlink', { provider })` (Inertia, no raw fetch, no page reload).
- Auto-login from the main club flow uses a signed URL (`User::generateUrlForForceLogin()` → `route('auth')`).
- Avatar upload: `AvatarPicker.vue` (`components/ui/AvatarPicker.vue`) posts the cropped blob with `useHttp` to `POST /avatar/upload` and reads `data.avatar`; the URL is then saved as the `avatar` field of the settings form. There is no `lib/upload.ts` anymore.

## 8. Environment & local setup (Windows)

Requirements: PHP `^8.5`, Composer, MySQL, Node 20+.

```bash
composer install
npm install
```

1. Create `.env` manually — **there is no `.env.example`** and `.env` is gitignored. Include at least `APP_KEY` (generate via `php artisan key:generate`), `APP_URL`, DB settings (`DB_CONNECTION=mysql`, `DB_DATABASE/HOST/PORT/USERNAME/PASSWORD`), Redis (`CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `REDIS_HOST/PORT/PASSWORD`, `REDIS_CACHE_DB=1`, `REDIS_DB=0`), and optional feature keys: `VK_ID_APP_ID`, `VK_ID_REDIRECT_URL`, `YANDEX_CLIENT_ID`, `YANDEX_CLIENT_SECRET`, `YANDEX_REDIRECT_URI`, `SMARTCAPTCHA_SITE_KEY`, `SMARTCAPTCHA_SERVER_KEY`, `LESSON_DEFAULT_PRICE`, `LESSON_DEFAULT_DURATION`, `YANDEX_METRIKA_ID`, feedback bots (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_WEBHOOK_SECRET`, `MAX_BOT_TOKEN`, `MAX_OWNER_ID`, `MAX_BOT_USERNAME`, `MAX_WEBHOOK_SECRET`, `FEEDBACK_PRIMARY_BOT`), and the personal tutor bot (`TELEGRAM_STUDENTS_BOT_TOKEN`, `TELEGRAM_STUDENTS_BOT_USERNAME`, `TELEGRAM_STUDENTS_WEBHOOK_SECRET`).
2. Serve locally: `php artisan serve` (or Herd/XAMPP) in one terminal and `npm run dev` (Vite) in another. Vite's `publicDirectory` is `public_html`, so built assets land in `public_html/build` (gitignored).
3. If the page needs DB schema changes, prepare the migration file but **do not run it** — ask the user to apply it.

Production is a shared host with docroot `public_html`; the agent has no access to it. Never attempt remote/CLI operations against it.

## 9. Verification before you finish

Run these on every change and fix what they report:

```bash
npx vue-tsc --noEmit     # type-check all TS/Vue (always)
npm run build            # when any Vue/TS/CSS or Vite config changed
php -l path/to/file.php  # syntax-check each changed PHP file
```

There is no automated test suite, no linter and no formatter configured — do not invent or run alternative quality commands, and do not add config for them.

Cache/Redis work additionally requires: Redis reachable (`CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, sessions in db 0 / cache in db 1), `config:cache`/`route:cache`/`view:cache` re-run by the user after `.env` or config changes, and a manual check that a mutation (student/lesson/topic/status/review, promo banner, subject, role, subscription) is reflected on the next page load instead of a stale cached value.

## 10. Changelog

The changelog is a hardcoded, versioned array inside `ChangelogController::getChangelog()` and is shown in `ChangelogModal` (fetched via `useHttp` from `/changelog`). New versions are prepended as `['version' => '1.x', 'date' => 'YYYY-MM-DD', 'categories' => [['title' => ..., 'items' => [...]]]]`.

**The changelog is maintained automatically from your sessions.** When your work in a session produces user-facing changes, prepend a new entry describing them (wording in Russian, user-facing tone, grouped under emoji categories like the existing entries). Do not wait to be asked, and do not only do it when the user mentions the changelog.

The `date` field must always be the **current date** in `YYYY-MM-DD` format (the day you make the change), never a past or planned future date.

The only thing to ask the user is the bump type:
- **major** — `X.0` (e.g. `2.0`): releases that rewrite the main modules or bump core dependencies; only when the user asks for it;
- **minor** — `1.*` (e.g. `1.5`): new features or noticeable behavior/UI changes;
- **patch** — `1.*.*` (e.g. `1.5.1`): fixes and small tweaks.

Only include changes that make sense to describe to a user of the system (new functionality, visible UI/behavior changes, user-visible fixes). **Do not describe internal/technical changes** (refactors, code cleanup, package/infra moves, type-only edits) — omit them entirely from the entry. **Do not describe admin-only changes** (everything under `/admin/*`, e.g. the subjects/promo/UI-kit sections) — they are not visible to regular users, so a session that only touches the admin part adds no changelog entry at all.

## 11. Do / Don't checklist

**Do**
- Match the existing style of the file you edit; keep new PHP modern but consistent with its neighbors.
- Submit mutations with Inertia (`router.post`/`useForm`); controllers reply `back()->with('success'|'error', ...)` or `back()->withErrors($validator)`, and the redirect visit refreshes props with no browser reload (§7).
- Add every new backend string to both `ru` and `en` `messages.php` and expose it via `lng()`.
- Write every new/edited lang string with non-breaking spaces already in place — literal U+00A0, never `&nbsp;`, in both `messages.php` and `validation.php` (§5).
- Reuse the local UI kit, Tailwind theme tokens, existing pages/popups/helpers. Consult the UI-kit page (`/admin/uikit`) before styling and update it when primitives change.
- Use `Checkbox`/`Radio`/`Select` for every choice/toggle and `IconButton` for icon actions (edit/delete/restore/show) instead of native controls; feed `Select` a typed `SelectOption<T>[]`.
- Render every numeric stat tile (label + big number + icon badge) with `StatCard` — its number must always count up from zero; never copy the markup into a page.
- Keep tariff limits/prices in `config/tariffs.php`; enforce quotas on creation and gate the matching UI "add" buttons via the shared `tariff` prop.
- Give success/error feedback through `useToast()` (`lib/toast.ts`) — success/info auto-dismiss, errors persist until closed; keep field validation inline.
- Run the §9 checks and fix all reported errors before reporting completion.
- Write short Russian comments only where they explain non-obvious decisions.

**Don't**
- Touch git in any way (§2).
- Read or modify `.env`, or output secrets/credentials.
- Run migrations, seeds, or `artisan config:cache`/`optimize` without explicit permission.
- Add packages, refactor `App\Model`/`App\Common`/legacy controllers, or add tests unless asked (the changelog is the exception — it updates automatically at the end of a session, see §10).
- Add new `fetch` JSON endpoints or `window.location.reload()` patterns; prefer Inertia-native flows.
- Write native `<select>`, `<input type="checkbox">` or `<input type="radio">` in pages/components — use `Select`/`Checkbox`/`Radio` (§2/§6).
- Re-add inline flash banners or blocking alert modals for user messages — the global `Toaster` handles them (§6/§7).
- Hardcode plan limits/prices or Russian plan strings; add payment flows or self-service plan switching (§2).
- Write English or heavy PHPDoc comments in Russian-oriented code — keep comments minimal and in Russian.
