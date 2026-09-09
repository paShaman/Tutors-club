<?php

namespace App\Http\Controllers;

use App\Image;
use App\Model\User;
use App\Services\VkIdService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Регистрация пользователя.
     */
    public function register(Request $request): RedirectResponse
    {
        $rules = [
            'email'                 => 'required|email',
            'password'              => 'required',
            'password_confirmation' => 'required|same:password',
            'smart-token'           => 'required|string',
        ];

        $request->validate($rules);

        if (!$this->verifySmartCaptcha((string) $request->input('smart-token'), (string) $request->ip())) {
            return redirect()->back()->with('error', lng('error.captcha'));
        }

        $email = mb_strtolower(trim((string) $request->input('email')));

        $existing = User::where('email', $email)->first();
        if (!empty($existing)) {
            return redirect()->back()->with('error', lng('duplicate_email'));
        }

        $user = new User();
        $user->email       = $email;
        $user->password    = Hash::make((string) $request->input('password'));
        $user->first_name  = $request->input('first_name', '');
        $user->last_name   = $request->input('last_name', '');
        $user->middle_name = $request->input('middle_name', '');
        $user->date_agree  = DB::raw('now()');

        try {
            $user->save();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', lng('error.register'));
        }

        event(new Registered($user));

        Auth::login($user, true);

        return redirect()->intended(route('home'));
    }

    /**
     * Авторизация пользователя.
     */
    public function login(Request $request): RedirectResponse
    {
        $rules = [
            'email'    => 'required|email',
            'password' => 'required',
        ];

        $request->validate($rules);

        $credentials = [
            'email'    => mb_strtolower(trim((string) $request->input('email'))),
            'password' => (string) $request->input('password'),
        ];

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        return redirect()->back()->with('error', lng('error.login'));
    }

    /**
     * Принудительная авторизация по ссылке с подписью.
     */
    public function auth(): RedirectResponse
    {
        $userId = request()->get('user');

        Auth::loginUsingId($userId, true);

        return redirect(route('home'));
    }

    /**
     * Авторизация через VK ID.
     *
     * Клиент обменивает авторизационный код на токены (VKID.Auth.exchangeCode)
     * и присылает сюда access_token. Сервер проверяет токен у VK и находит
     * либо создаёт пользователя, привязанного к аккаунту VK.
     */
    public function vkontakte(Request $request): RedirectResponse
    {
        $accessToken = (string) $request->input('access_token');

        if ($accessToken === '') {
            return redirect()->back()->with('error', lng('error.vk'));
        }

        $profile = app(VkIdService::class)->fetchUserInfo($accessToken);

        if ($profile === null || empty($profile['user_id'])) {
            return redirect()->back()->with('error', lng('error.vk'));
        }

        $socialId = (string) $profile['user_id'];
        $email = isset($profile['email']) && $profile['email'] !== ''
            ? mb_strtolower(trim((string) $profile['email']))
            : null;

        $user = $this->findUserByVkAccount($socialId, $email);

        if ($user === null) {
            $user = new User();
            $user->email      = $this->buildUniqueEmail($email, $socialId);
            $user->password   = '';
            $user->first_name = isset($profile['first_name']) ? (string) $profile['first_name'] : '';
            $user->last_name  = isset($profile['last_name']) ? (string) $profile['last_name'] : '';
            $user->middle_name = '';
            $user->avatar     = isset($profile['avatar']) && $profile['avatar'] !== ''
                ? Image::createImgUrl((string) $profile['avatar'], ['fit' => Image::AVATAR_SIZE])
                : null;
            $user->date_agree = DB::raw('now()');

            try {
                $user->save();
            } catch (\Exception $e) {
                return redirect()->back()->with('error', lng('error.register'));
            }
        } elseif ($email !== null && $this->isSyntheticVkEmail((string) $user->email, $socialId)) {
            $this->replaceVkEmail($user, $email);
        }

        // Привязываем аккаунт VK к пользователю.
        DB::table('users_social')->updateOrInsert(
            ['social' => VkIdService::SOCIAL_VKONTAKTE, 'social_id' => $socialId],
            ['user_id' => $user->id, 'updated_at' => now()]
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Поиск пользователя по привязке VK либо по email из профиля VK.
     */
    private function findUserByVkAccount(string $socialId, ?string $email): ?User
    {
        $vkUserId = DB::table('users_social')
            ->where('social', VkIdService::SOCIAL_VKONTAKTE)
            ->where('social_id', $socialId)
            ->value('user_id');

        if ($vkUserId) {
            $user = User::find($vkUserId);
            if ($user) {
                return $user;
            }
        }

        if ($email !== null) {
            return User::where('email', $email)->first();
        }

        return null;
    }

    /**
     * Формирует уникальный email для аккаунта без email в профиле VK.
     */
    private function buildUniqueEmail(?string $email, string $socialId): string
    {
        if ($email !== null && User::where('email', $email)->doesntExist()) {
            return $email;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'tutors-club.ru';

        return 'vk-' . $socialId . '@' . $host;
    }

    /**
     * Является ли email сгенерированным для аккаунта VK без почты.
     */
    private function isSyntheticVkEmail(string $email, string $socialId): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'tutors-club.ru';

        return $email === 'vk-' . $socialId . '@' . $host;
    }

    /**
     * Заменяет сгенерированный email аккаунта VK на почту из профиля VK.
     * Почта не трогается, если уже занята другим пользователем.
     */
    private function replaceVkEmail(User $user, string $email): void
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

    /**
     * Выход.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect(route('login'));
    }

    /**
     * Проверка токена Yandex SmartCaptcha.
     */
    private function verifySmartCaptcha(string $token, string $ip): bool
    {
        $secret = config('services.yandex_smartcaptcha.server_key');

        if (empty($secret) || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://smartcaptcha.cloud.yandex.ru/validate', [
                    'secret' => $secret,
                    'token'  => $token,
                    'ip'     => $ip,
                ]);

            if (!$response->successful()) {
                return false;
            }

            return $response->json('status') === 'ok';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
