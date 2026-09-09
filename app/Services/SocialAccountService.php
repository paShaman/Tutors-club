<?php

declare(strict_types=1);

namespace App\Services;

use App\Image;
use App\Model\User;
use Illuminate\Support\Facades\DB;

final class SocialAccountService
{
    public const KNOWN_PROVIDERS = [
        'vkontakte',
        'yandex',
        'facebook',
        'google',
    ];

    private const LABELS = [
        'vkontakte' => 'VK ID',
        'yandex'    => 'Яндекс ID',
        'facebook'  => 'Facebook',
        'google'    => 'Google',
    ];

    private const EMAIL_PREFIXES = [
        'vkontakte' => 'vk',
        'yandex'    => 'yandex',
        'facebook'  => 'fb',
        'google'    => 'google',
    ];

    public function label(string $provider): string
    {
        return self::LABELS[$provider] ?? $provider;
    }

    public function supports(string $provider): bool
    {
        return in_array($provider, self::KNOWN_PROVIDERS, true);
    }

    public function normalizeEmail($email): ?string
    {
        if (!is_string($email)) {
            return null;
        }

        $email = mb_strtolower(trim($email));

        return $email !== '' ? $email : null;
    }

    public function bindingOwner(string $provider, string $socialId): ?int
    {
        $userId = DB::table('users_social')
            ->where('social', $provider)
            ->where('social_id', $socialId)
            ->value('user_id');

        return $userId !== null ? (int) $userId : null;
    }

    public function findUser(string $provider, string $socialId, ?string $email): ?User
    {
        $ownerId = $this->bindingOwner($provider, $socialId);

        if ($ownerId !== null) {
            $user = User::find($ownerId);

            if ($user) {
                return $user;
            }
        }

        if ($email !== null) {
            return User::where('email', $email)->first();
        }

        return null;
    }

    public function linkToUser(int $userId, string $provider, string $socialId): void
    {
        DB::table('users_social')->updateOrInsert(
            ['social' => $provider, 'social_id' => $socialId],
            ['user_id' => $userId, 'updated_at' => now()]
        );
    }

    public function createUser(string $provider, string $socialId, array $profile): User
    {
        $email = $this->normalizeEmail($profile['email'] ?? null);

        $user = new User();
        $user->email       = $this->buildSyntheticEmail($provider, $email, $socialId);
        $user->password    = '';
        $user->first_name  = isset($profile['first_name']) ? (string) $profile['first_name'] : '';
        $user->last_name   = isset($profile['last_name']) ? (string) $profile['last_name'] : '';
        $user->middle_name = '';
        $user->avatar      = isset($profile['avatar']) && $profile['avatar'] !== ''
            ? Image::createImgUrl((string) $profile['avatar'], ['fit' => Image::AVATAR_SIZE])
            : null;
        $user->date_agree  = DB::raw('now()');
        $user->save();

        return $user;
    }

    public function isSyntheticEmail(string $provider, string $email, string $socialId): bool
    {
        return $email === $this->syntheticEmail($provider, $socialId);
    }

    public function replaceSyntheticEmail(User $user, string $email): void
    {
        $occupied = User::where('email', $email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($occupied) {
            return;
        }

        $user->email = $email;
        $user->save();
    }

    private function buildSyntheticEmail(string $provider, ?string $email, string $socialId): string
    {
        if ($email !== null && User::where('email', $email)->doesntExist()) {
            return $email;
        }

        return $this->syntheticEmail($provider, $socialId);
    }

    private function syntheticEmail(string $provider, string $socialId): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'tutors-club.ru';
        $prefix = self::EMAIL_PREFIXES[$provider] ?? $provider;

        return $prefix . '-' . $socialId . '@' . $host;
    }
}
