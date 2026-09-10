<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\VkIdService;
use App\Services\YandexIdService;
use Illuminate\Http\Request;
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
                ] : null,
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error'   => fn (): ?string => $request->session()->get('error'),
            ],
            'agreements' => config('agreements.documents', []),
            'locale'     => app()->getLocale(),
            'locales'    => collect(config('locales.available', []))
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
}