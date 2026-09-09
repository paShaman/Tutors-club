# AGENTS.md — Tutors Club Cabinet

Guidance for AI coding agents working in this repository. Read this file before making changes.

---

## 1. Project overview

**Tutors Club Cabinet** is the main product of the Tutors-club project (there is no separate landing/marketing app yet). It is a personal web cabinet for private tutors where they can:

- manage their **students** (add, edit, soft-delete, avatars, dynamic school class);
- plan and track **lessons** (FullCalendar schedule, price, duration, paid/future status);
- watch **statistics** on the dashboard (weekly workload chart, monthly earnings);
- manage their **profile** (name, avatar with client-side cropping, password, linked social accounts).

The user-facing UI is Russian; backend locale is `ru` (with `en` translation files that must be kept in sync for backend strings, see §7).

## 2. Non-negotiable rules (read first)

- **Git is completely off-limits.** Do not stage, commit, push, branch, reset, stash, merge, rebase, or craft commit messages. Do not even run `git log`/`git status`/`git diff` unless the user explicitly asks. Version control is done by the user only.
- **Never touch `.env`, and never log/print secrets** (DB credentials, `APP_KEY`, `VK_ID_*`, `YANDEX_*`, `SMARTCAPTCHA_*`). There is no `.env.example` in the repo.
- **There is no access to production.** The app runs on a shared host (docroot = `public_html`) with MySQL. Do not run any artisan/CLI command against production, and do not assume production has artisan/Node available.
- **Never run `php artisan migrate` / `db:seed` / config or cache commands** — even locally — without an explicit user request. Propose the migration/change and let the user apply it.
- **Do not install new Composer or npm packages** without asking first. Note: `axios` is **not** declared in `package.json`; it only exists transitively (via `@inertiajs/core`). Do not add new code that imports it directly (ChangelogModal currently does) unless you first add it as a real dependency.
- **Do not write tests.** There is no `tests/` directory, no test script, and tests are intentionally not part of the workflow yet. Do not scaffold PHPUnit/Pest/component-test infrastructure.
- **Do not refactor legacy code** (`app/Model`, `App\Common`, `App\Access`, `App\Notification`, `App\Form`, `App\Image`, old controllers/models) unless the task explicitly requires it. Match the surrounding file's style when you do edit one.
- **Do not update the changelog** (hardcoded in `ChangelogController`) unless explicitly asked.
- **Do not add new `window.location.reload()` calls** and prefer migrating touched flows away from full reloads (see §8).

## 3. Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP `^8.5`, Laravel `^13.0` (declarative `bootstrap/app.php`), Inertia Laravel v2 |
| Frontend | Vue 3 (`<script setup>`), TypeScript, Inertia Vue v2 |
| Build | Vite (`laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite`), output goes to `public_html/build` (gitignored) |
| Styling | Tailwind CSS v4 with `@theme` design tokens defined in `resources/css/app.css` (shadcn-like tokens, glass utilities); fonts Geist/Inter |
| UI kit | Local `resources/js/components/ui/*` built on radix-vue, `class-variance-authority`, `clsx`, `tailwind-merge`, `cn()` from `@/lib/utils`; icons from `lucide-vue-next` |
| Widgets | `@fullcalendar/*` (calendar), `chart.js` + `vue-chartjs` (dashboard charts) |
| Auth | Email/password, **VK ID** (`@vkid/sdk`, config `services.vkid`), **Yandex ID** OAuth (`services.yandex`), Yandex SmartCaptcha on registration, signed auto-login URL |
| Routing on client | Ziggy `@routes` is present in the Blade template, but frontend code uses **literal URL strings** (e.g. `/students/edit`), not `route()` |
| Tests | none |
| Lint/format | none configured (no ESLint/Prettier/Pint/PHPStan) |

Path alias: `@/*` → `resources/js/*` (configured in both `vite.config.ts` and `tsconfig.json`).

## 4. Repository map

```
app/
├── Access.php                 # legacy role ID constants (unused)
├── Common.php                 # legacy helpers (pagination etc.)
├── Form.php                   # builds modal/form definitions passed as Inertia props
├── Image.php                  # avatar storage/cropping/cleanup helpers
├── Notification.php           # legacy session flash helpers
├── helpers.php                # lng() localization helper
├── Http/
│   ├── Controllers/           # Auth, Avatar, Calendar, Changelog, Dashboard, Lesson,
│   │                          # Page, Student, User, ControllerHelper
│   └── Middleware/            # Authenticate, HandleInertiaRequests, ...
├── Model/                     # LEGACY Eloquent namespace: User, Student, Lesson,
│                              # User, Student, Lesson, Page  (NOT App\Models)
├── Services/                  # SocialAccountService, SocialOAuthProvider,
│                              # VkIdService, YandexIdService
└── Providers/
routes/
├── web.php                    # THE single routing file (all HTTP routes)
└── api.php                    # present but unused — no routes in use go through it
config/
├── services.php               # vkid / yandex / yandex_smartcaptcha credentials
├── agreements.php             # registration agreement documents (shared via Inertia)
└── lesson.php                 # LESSON_DEFAULT_PRICE (3000), LESSON_DEFAULT_DURATION (60)
resources/
├── css/app.css                # Tailwind v4 entry: @theme tokens + global styles
├── lang/{ru,en}/              # messages.php (backend strings), validation.php, js.php, mail.php
├── views/app.blade.php        # single Blade shell (@routes, @vite, @inertia)
└── js/
    ├── app.ts                 # createInertiaApp entry (import.meta.glob Pages)
    ├── Pages/                 # one .vue per route component: Dashboard, Students,
    │                          # Lessons, Calendar, Settings, Login, Register
    ├── Layouts/AppLayout.vue  # sidebar + layout; pages opt in via defineOptions
    ├── components/
    │   ├── ui/                # Button, Card, CardHeader, CardTitle, UserAvatar,
    │   │                      # AvatarPicker, ImageCropper
    │   ├── popups/            # AlertPopup, ConfirmDialog, ChangelogModal,
    │   │                      # StudentFormPopup, LessonFormPopup
    │   └── social/            # SocialAuth, VkIdAuth, YandexAuth
    ├── lib/                   # utils.ts (cn), upload.ts (avatar upload), social.ts
    └── types/index.ts         # SharedProps + PageProps augmentation
public_html/                   # web root (Laravel public dir via usePublicPath)
```

## 5. Backend conventions (PHP)

- **Routing**: everything lives in `routes/web.php`. Page endpoints render Inertia; CRUD/payload endpoints historically return JSON (see §8). Route names use dot notation (`auth.yandex`, `lessons`).
- **Controllers** live in `App\Http\Controllers`. Page methods return `Inertia::render('PageName', props)`. `ControllerHelper::resultSuccess()` / `resultError()` wrap JSON as `{ success: bool, data: ... }`; validation failures are `{ success: false, data: { field: message } }`.
- **Models** use the legacy namespace `App\Model` (singular). Keep using it for new models. Relations: `User` ↔ `Student` (many-to-many via `students_to_users`), `Student` has many `Lesson`.
- **Localization**: user-facing backend strings MUST go through the `lng('...')` helper (dot key under `messages.php`), e.g. `lng('success.add_student')`. **Every new key must be added to BOTH `resources/lang/ru/messages.php` and `resources/lang/en/messages.php`.** Group keys under `success.*`, `error.*`, `title`, etc. See existing usage in controllers.
- **Roles**: `roles`/`roles_to_users` tables and `Access` constants are an unused rudiment from an old version and are not part of the current product. Do not introduce new role logic; if roles return later, this is where it goes.
- **PHP style for NEW files**: add `declare(strict_types=1);`, typed signatures/return types, `final class` where sensible, aligned multi-line arrays, no noisy PHPDoc — write short Russian comments only where they explain "why". Do not retro-edit old files to this style.
- Middleware: `auth`, `guest`, `signed` (auto-login link). Shared Inertia props are assembled in `app/Http/Middleware/HandleInertiaRequests.php` (`auth.user`, `flash`, `social`, `agreements`) — when you add globally shared data, extend this class AND `resources/js/types/index.ts` (`SharedProps`).

## 6. Frontend conventions (Vue/TS)

- A **page** = `resources/js/Pages/<Name>.vue`, referenced by the controller's `Inertia::render('<Name>', ...)`. Layout opt-in: `defineOptions({ layout: AppLayout })`. Set page title with `<Head title="..."/>` (Russian).
- Use `<script setup lang="ts">`. Type props with `defineProps<{...}>()`; typing is **pragmatic** — an occasional local `any` is acceptable, but keep shared/global types in `types/index.ts`.
- Import with the `@/` alias for `Layouts/`, `components/`, `lib/`; use relative paths within a directory.
- **Reuse existing UI**, don't restyle components: `components/ui/*` (Button with variants, Card/CardHeader/CardTitle, UserAvatar), `components/popups/*` (AlertPopup, ConfirmDialog, form popups). Buttons are `Button`; forms live in popups bound to local refs.
- Styling: Tailwind utility classes with the design-token palette from `resources/css/app.css` (`bg-background`, `text-foreground`, `bg-primary/...`, `border-border`, tokens like `--color-primary: hsl(252 87% 67%)`, radius 0.75rem). Do not introduce ad-hoc hex colors; extend the `@theme` block if a token is genuinely needed.
- Icons: `lucide-vue-next`. Charts: register ChartJS modules where used. Calendar: `@fullcalendar/vue3`.
- Vue components are single-file with `lang="ts"`; no CSS-in-JS. Shared helpers: `cn()` in `lib/utils.ts`, `uploadAvatar()` in `lib/upload.ts`.
- The whole UI text is **hardcoded Russian** on the client — keep it that way; do not wire up client-side i18n.

## 7. Request/data flows

**How pages get data today (hybrid, legacy).**
- Page GET endpoints (e.g. `GET /students`, `GET /lessons`, `GET /calendar`, `GET /`) return an Inertia page with props computed server-side.
- Navigation and list **filtering** use Inertia: `<Link>` or `router.get('/lessons', { student_id })` (server re-renders the page with filtered props).
- **Mutations** (student/lesson create/edit/delete/pay) historically go through raw `fetch()` POSTs to literal JSON endpoints (`/students/edit`, `/students/delete`, `/lessons/edit`, `/lessons/delete`, `/lessons/pay`) returning `{ success, data }`; on success the page does `window.location.reload()`. **Social linking/unlinking is NOT part of this legacy pattern** — it already uses Inertia `router.post` (see below).

**Target conventions for NEW code (and when you touch an existing flow).**
- New screens/operations should be **Inertia-native**: render the page with props, submit with Inertia (`router.post`/`router.put`/`router.delete`, `useForm`), surface errors via Inertia/validation, and update the UI without a full page reload (e.g. `router.reload({ only: [...] })` or local state). Inertia handles CSRF automatically.
- Do **not** add new `fetch()` JSON endpoints or new `window.location.reload()` calls. If you must keep/repair an existing raw `fetch` call, follow the canonical helper in `resources/js/lib/upload.ts`: send `Accept: application/json`, `X-Requested-With: XMLHttpRequest`, and `X-XSRF-TOKEN` decrypted from the `XSRF-TOKEN` cookie; treat HTTP `419` as an expired session. Never send `_token` from a raw string.
- Server responses for user-facing messages come localized via `lng()`; keep `success.*` / `error.*` keys synced in ru + en.

**Social/auth flows.**
- Registration: agreement consent (props `agreements` from `config/agreements.php`) + Yandex SmartCaptcha.
- VK ID: `components/social/VkIdAuth.vue` posts to `/auth/vk` (register/login) or `/user/socials/link` (link) with the VK token; `router.post` is the correct mechanism.
- Yandex: OAuth redirect via `AuthController::yandex*` routes; linking via `/user/socials/link/yandex`.
- Unlink: `Pages/Settings.vue` already calls `router.post('/user/socials/unlink', { provider })` (Inertia, no raw fetch, no page reload).
- Auto-login from the main club flow uses a signed URL (`User::generateUrlForForceLogin()` → `route('auth')`).
- Avatar upload: `uploadAvatar()` (`lib/upload.ts`) → `POST /avatar/upload`, returns `data.avatar` URL.

## 8. Environment & local setup (Windows)

Requirements: PHP `^8.5`, Composer, MySQL, Node 20+.

```bash
composer install
npm install
```

1. Create `.env` manually — **there is no `.env.example`** and `.env` is gitignored. Include at least `APP_KEY` (generate via `php artisan key:generate`), `APP_URL`, DB settings (`DB_CONNECTION=mysql`, `DB_DATABASE/HOST/PORT/USERNAME/PASSWORD`), and optional feature keys: `VK_ID_APP_ID`, `VK_ID_REDIRECT_URL`, `YANDEX_CLIENT_ID`, `YANDEX_CLIENT_SECRET`, `YANDEX_REDIRECT_URI`, `SMARTCAPTCHA_SITE_KEY`, `SMARTCAPTCHA_SERVER_KEY`, `LESSON_DEFAULT_PRICE`, `LESSON_DEFAULT_DURATION`.
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

## 10. Changelog

The changelog is a hardcoded, versioned array inside `ChangelogController::getChangelog()` and is shown in `ChangelogModal` (fetched via axios from `/changelog`). New versions are prepended as `['version' => '1.x', 'date' => 'YYYY-MM-DD', 'categories' => [['title' => ..., 'items' => [...]]]]` with a top version number bump. **Only touch it when the user explicitly asks.**

## 11. Do / Don't checklist

**Do**
- Match the existing style of the file you edit; keep new PHP modern but consistent with its neighbors.
- Add every new backend string to both `ru` and `en` `messages.php` and expose it via `lng()`.
- Reuse the local UI kit, Tailwind theme tokens, existing pages/popups/helpers.
- Run the §9 checks and fix all reported errors before reporting completion.
- Write short Russian comments only where they explain non-obvious decisions.

**Don't**
- Touch git in any way (§2).
- Read or modify `.env`, or output secrets/credentials.
- Run migrations, seeds, or `artisan config:cache`/`optimize` without explicit permission.
- Add packages, refactor `App\Model`/`App\Common`/`App\Access`/legacy controllers, add tests, or update the changelog unless asked.
- Add new `fetch` JSON endpoints or `window.location.reload()` patterns; prefer Inertia-native flows.
- Write English or heavy PHPDoc comments in Russian-oriented code — keep comments minimal and in Russian.
