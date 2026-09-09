<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class YandexIdService implements SocialOAuthProvider
{
    public const SOCIAL_YANDEX = 'yandex';

    private const AUTHORIZE_URL = 'https://oauth.yandex.ru/authorize';
    private const TOKEN_URL = 'https://oauth.yandex.ru/token';
    private const INFO_URL = 'https://login.yandex.ru/info';
    private const SCOPES = 'login:info login:email login:avatar';

    public function key(): string
    {
        return self::SOCIAL_YANDEX;
    }

    public function configured(): bool
    {
        return (string) config('services.yandex.client_id') !== ''
            && (string) config('services.yandex.client_secret') !== '';
    }

    public function authorizeUrl(string $state): ?string
    {
        $clientId = (string) config('services.yandex.client_id');

        if ($clientId === '') {
            return null;
        }

        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $this->redirectUri(),
            'scope'         => self::SCOPES,
            'state'         => $state,
        ]);

        return self::AUTHORIZE_URL . '?' . $query;
    }

    public function profileFromCode(string $code): ?array
    {
        $token = $this->fetchAccessToken($code);

        if ($token === null) {
            return null;
        }

        return $this->fetchUserInfo($token);
    }

    private function fetchAccessToken(string $code): ?string
    {
        if (!$this->configured()) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::TOKEN_URL, [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'client_id'     => (string) config('services.yandex.client_id'),
                    'client_secret' => (string) config('services.yandex.client_secret'),
                ]);

            if (!$response->successful()) {
                return null;
            }

            $token = $response->json('access_token');

            return is_string($token) && $token !== '' ? $token : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchUserInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders(['Authorization' => 'OAuth ' . $accessToken])
                ->timeout(10)
                ->get(self::INFO_URL, ['format' => 'json']);

            if (!$response->successful()) {
                return null;
            }

            $info = $response->json();

            if (!is_array($info) || empty($info['id'])) {
                return null;
            }

            return [
                'user_id'    => (string) $info['id'],
                'email'      => $this->email($info),
                'first_name' => isset($info['first_name']) ? (string) $info['first_name'] : '',
                'last_name'  => isset($info['last_name']) ? (string) $info['last_name'] : '',
                'avatar'     => $this->avatarUrl($info),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function email(array $info): ?string
    {
        if (!empty($info['default_email']) && is_string($info['default_email'])) {
            return $info['default_email'];
        }

        if (!empty($info['emails'][0]) && is_string($info['emails'][0])) {
            return $info['emails'][0];
        }

        return null;
    }

    private function avatarUrl(array $info): ?string
    {
        if (empty($info['default_avatar_id']) || !empty($info['is_avatar_empty'])) {
            return null;
        }

        return 'https://avatars.yandex.net/get-yapic/' . $info['default_avatar_id'] . '/islands-200';
    }

    private function redirectUri(): string
    {
        $configured = (string) config('services.yandex.redirect_uri');

        return $configured !== '' ? $configured : route('auth.yandex.callback');
    }
}
