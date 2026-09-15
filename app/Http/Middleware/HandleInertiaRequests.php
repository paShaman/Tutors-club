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
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
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
            'translations' => fn (): array => $this->translations(),
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
     * Переводы текущей локали.
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
            CacheKeys::langMessages($locale, $this->langSignature($locale)),
            now()->addDay(),
            fn (): array => (array) trans('messages'),
        );
    }

    /**
     * Подпись файла переводов: меняется при любом деплое с правкой строк.
     */
    private function langSignature(string $locale): string
    {
        $candidates = [
            app()->langPath() . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'messages.php',
            resource_path('lang' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'messages.php'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return filemtime($path) . '-' . filesize($path);
            }
        }

        return 'unknown';
    }
}