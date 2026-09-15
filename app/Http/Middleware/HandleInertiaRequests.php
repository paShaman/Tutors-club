<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Model\PromoBanner;
use App\Services\FeedbackBotService;
use App\Services\VkIdService;
use App\Services\YandexIdService;
use App\Support\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * «Версия» словарей, уже отданных этому клиенту (язык + признак админа).
     * Хранится в сессии, чтобы при смене языка или роли переводы ушли заново,
     * а не остались в памяти клиента от предыдущей страницы.
     */
    private const TRANSLATIONS_VARIANT = 'inertia.translations.variant';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $vkConfigured = (int) config('services.vkid.app_id') > 0
            && (string) config('services.vkid.redirect_url') !== '';

        $feedbackBots = app(FeedbackBotService::class);
        $primaryBot   = $feedbackBots->primary();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'          => $request->user()->id,
                    'email'       => $request->user()->email,
                    'avatar'      => $request->user()->avatar,
                    'first_name'  => $request->user()->first_name,
                    'last_name'   => $request->user()->last_name,
                    'middle_name' => $request->user()->middle_name,
                    'name'        => $request->user()->name,
                    'has_password' => $request->user()->password !== '',
                    'is_admin'    => $request->user()->isAdmin(),
                ] : null,
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error'   => fn (): ?string => $request->session()->get('error'),
            ],
            'agreements' => config('agreements.documents', []),
            'requisites' => config('company.requisites', []),
            'feedback'   => [
                'primary' => $primaryBot,
                'bots'    => [
                    [
                        'key'        => FeedbackBotService::TELEGRAM,
                        'url'        => $feedbackBots->telegramBotUrl(),
                        'configured' => $feedbackBots->telegramConfigured(),
                        'primary'    => $primaryBot === FeedbackBotService::TELEGRAM,
                    ],
                    [
                        'key'        => FeedbackBotService::MAX,
                        'url'        => $feedbackBots->maxBotUrl(),
                        'configured' => $feedbackBots->maxConfigured(),
                        'primary'    => $primaryBot === FeedbackBotService::MAX,
                    ],
                ],
            ],
            'tariff' => fn (): ?array => $request->user()
                ? app(\App\Services\TariffService::class)->payload($request->user())
                : null,
            'promoBanners' => fn (): array => $request->user()
                ? PromoBanner::activePayloads()
                : [],
            'locale'       => app()->getLocale(),
            'locales'      => collect(config('locales.available', []))
                ->map(fn (string $label, string $code): array => ['code' => $code, 'label' => $label])
                ->values()
                ->all(),
            'social' => [
                [
                    'key'         => VkIdService::SOCIAL_VKONTAKTE,
                    'configured'  => $vkConfigured,
                    'app'         => $vkConfigured ? (int) config('services.vkid.app_id') : null,
                    'redirectUrl' => $vkConfigured ? (string) config('services.vkid.redirect_url') : null,
                ],
                [
                    'key'        => YandexIdService::SOCIAL_YANDEX,
                    'configured' => app(YandexIdService::class)->configured(),
                ],
            ],
        ];
    }

    /**
     * Переводы отдаём один раз за клиентскую сессию (once-prop): при навигации
     * внутри приложения Inertia не пересылает словари, а берёт их из памяти клиента.
     * Админские строки (admin.php) получают только администраторы.
     *
     * @return array<string, \Inertia\OnceProp>
     */
    public function shareOnce(Request $request): array
    {
        $locale  = (string) app()->getLocale();
        $isAdmin = (bool) $request->user()?->isAdmin();
        $variant = $locale . '|' . ($isAdmin ? '1' : '0');

        // Подпись файла в ключе once-prop: после деплоя с правкой строк
        // ключ меняется и переводы приходят заново, не дожидаясь перезагрузки.
        $props = [
            'translations' => Inertia::once(fn (): array => $this->translations())
                ->as('translations.' . $locale . '.' . $this->langSignature($locale, 'messages')),
        ];

        if ($isAdmin) {
            $props['adminTranslations'] = Inertia::once(fn (): array => $this->adminTranslations())
                ->as('adminTranslations.' . $locale . '.' . $this->langSignature($locale, 'admin'));
        }

        $alreadySent = $request->session()->get(self::TRANSLATIONS_VARIANT) === $variant;

        if (! $alreadySent) {
            // первая загрузка документа либо смена языка/роли — словари устарели
            foreach ($props as $prop) {
                $prop->fresh();
            }

            $request->session()->put(self::TRANSLATIONS_VARIANT, $variant);
        }

        return $props;
    }

    /**
     * Переводы текущей локали (messages.php).
     *
     * Ключ включает подпись файла messages.php, поэтому после деплоя
     * с новыми строками кэш обновляется сам.
     *
     * @return array<string, mixed>
     */
    private function translations(): array
    {
        $locale = (string) app()->getLocale();

        return Cache::remember(
            CacheKeys::langMessages($locale, $this->langSignature($locale, 'messages')),
            now()->addDay(),
            fn (): array => (array) trans('messages'),
        );
    }

    /**
     * Админские переводы (admin.php). В общий словарь не попадают.
     *
     * @return array<string, mixed>
     */
    private function adminTranslations(): array
    {
        $locale = (string) app()->getLocale();

        return Cache::remember(
            CacheKeys::langAdmin($locale, $this->langSignature($locale, 'admin')),
            now()->addDay(),
            fn (): array => (array) trans('admin'),
        );
    }

    /**
     * Подпись файла переводов: меняется при любом деплое с правкой строк.
     */
    private function langSignature(string $locale, string $file): string
    {
        $candidates = [
            app()->langPath() . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $file . '.php',
            resource_path('lang' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $file . '.php'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return filemtime($path) . '-' . filesize($path);
            }
        }

        return 'unknown';
    }
}
