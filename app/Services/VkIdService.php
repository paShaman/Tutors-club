<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class VkIdService
{
    public const SOCIAL_VKONTAKTE = 'vkontakte';

    /**
     * Есть ли настройки приложения VK ID.
     */
    public function configured(): bool
    {
        return (int) config('services.vkid.app_id') > 0
            && (string) config('services.vkid.redirect_url') !== '';
    }

    /**
     * Профиль пользователя VK по access_token (проверка токена на стороне VK).
     */
    public function fetchUserInfo(string $accessToken): ?array
    {
        $appId = (int) config('services.vkid.app_id');
        $baseUrl = rtrim((string) config('services.vkid.base_url'), '/');

        if ($appId <= 0 || $baseUrl === '') {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post($baseUrl . '/oauth2/user_info?client_id=' . $appId, [
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $user = $response->json('user');

            return is_array($user) ? $user : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
