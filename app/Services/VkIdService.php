<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class VkIdService
{
    public const SOCIAL_VKONTAKTE = 'vkontakte';

    private const SCOPES = 'vkid.personal_info email';
    private const CODE_CHALLENGE_METHOD = 'S256';

    public function key(): string
    {
        return self::SOCIAL_VKONTAKTE;
    }

    /**
     * Есть ли настройки приложения VK ID.
     */
    public function configured(): bool
    {
        return (int) config('services.vkid.app_id') > 0
            && (string) config('services.vkid.redirect_url') !== '';
    }

    /**
     * URL страницы авторизации VK ID (redirect-flow с PKCE).
     */
    public function authorizeUrl(string $state, string $codeChallenge): ?string
    {
        $appId = (int) config('services.vkid.app_id');
        $baseUrl = $this->baseUrl();

        if ($appId <= 0 || $baseUrl === '') {
            return null;
        }

        $query = http_build_query([
            'response_type'         => 'code',
            'client_id'             => $appId,
            'redirect_uri'          => $this->redirectUri(),
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => self::CODE_CHALLENGE_METHOD,
            'scope'                 => self::SCOPES,
        ]);

        return $baseUrl . '/authorize?' . $query;
    }

    /**
     * PKCE code_challenge из code_verifier: BASE64URL(SHA256(verifier)).
     */
    public function codeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Обмен кода подтверждения на токены на стороне бэкенда (PKCE).
     *
     * @return array<string, mixed>|null
     */
    public function exchangeCode(string $code, string $deviceId, string $codeVerifier, string $state): ?array
    {
        if (!$this->configured()) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post($this->baseUrl() . '/oauth2/auth?' . http_build_query([
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $this->redirectUri(),
                    'client_id'     => (int) config('services.vkid.app_id'),
                    'code_verifier' => $codeVerifier,
                    'device_id'     => $deviceId,
                    'state'         => $state,
                ]), [
                    'code' => $code,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $tokens = $response->json();

            return is_array($tokens) && !empty($tokens['access_token']) ? $tokens : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Профиль пользователя VK по access_token (проверка токена на стороне VK).
     *
     * @return array<string, mixed>|null
     */
    public function fetchUserInfo(string $accessToken): ?array
    {
        $appId = (int) config('services.vkid.app_id');
        $baseUrl = $this->baseUrl();

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

    private function baseUrl(): string
    {
        return rtrim((string) config('services.vkid.base_url'), '/');
    }

    private function redirectUri(): string
    {
        $configured = (string) config('services.vkid.redirect_url');

        return $configured !== '' ? $configured : route('auth.vk.callback');
    }
}
