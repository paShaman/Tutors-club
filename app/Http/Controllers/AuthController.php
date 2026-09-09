<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Services\SocialAccountService;
use App\Services\SocialOAuthProvider;
use App\Services\VkIdService;
use App\Services\YandexIdService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OAUTH_SESSION_KEY = 'social_oauth_flow';

    public function register(Request $request): RedirectResponse
    {
        $rules = [
            'email'                 => 'required|email',
            'password'              => 'required',
            'password_confirmation' => 'required|same:password',
            'smart-token'           => 'required|string',
            'agreement'             => 'required|accepted',
        ];

        $messages = [
            'agreement.required' => lng('error.agreement'),
            'agreement.accepted' => lng('error.agreement'),
        ];

        $request->validate($rules, $messages);

        if (!$this->verifySmartCaptcha((string) $request->input('smart-token'), (string) $request->ip())) {
            return redirect()->back()->with('error', lng('error.captcha'));
        }

        $email = mb_strtolower(trim((string) $request->input('email')));

        if (User::where('email', $email)->exists()) {
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

    public function auth(): RedirectResponse
    {
        $userId = request()->get('user');

        Auth::loginUsingId($userId, true);

        return redirect(route('home'));
    }

    public function vkontakte(Request $request): RedirectResponse
    {
        $socials = app(SocialAccountService::class);
        $provider = VkIdService::SOCIAL_VKONTAKTE;
        $label = $socials->label($provider);

        $accessToken = (string) $request->input('access_token');

        if ($accessToken === '') {
            return $this->socialFail(null, lng('error.social_login', ['provider' => $label]));
        }

        $profile = app(VkIdService::class)->fetchUserInfo($accessToken);

        if ($profile === null || empty($profile['user_id'])) {
            return $this->socialFail(null, lng('error.social_login', ['provider' => $label]));
        }

        return $this->socialLogin(
            $request,
            $provider,
            $profile,
            $request->boolean('register'),
            $request->boolean('agreement')
        );
    }

    public function yandex(Request $request): RedirectResponse
    {
        $service = app(YandexIdService::class);

        if (!$service->configured()) {
            return redirect()->route('login');
        }

        return $this->beginOauthFlow($request, $service, $request->boolean('register') ? 'register' : 'login');
    }

    public function yandexLink(): RedirectResponse
    {
        $service = app(YandexIdService::class);

        if (!$service->configured()) {
            return redirect()->back()->with('error', lng('error.social_link'));
        }

        return $this->beginOauthFlow(request(), $service, 'link');
    }

    public function yandexCallback(Request $request): RedirectResponse
    {
        $service = app(YandexIdService::class);
        $socials = app(SocialAccountService::class);

        $flow = $request->session()->pull(self::OAUTH_SESSION_KEY);

        if (!is_array($flow) || ($flow['provider'] ?? null) !== $service->key()) {
            return redirect()->route('login');
        }

        $mode = in_array($flow['mode'], ['login', 'register', 'link'], true) ? $flow['mode'] : 'login';
        $failRoute = $this->oauthModeRoute($mode);
        $label = $socials->label($service->key());

        if ($request->filled('error') || !hash_equals((string) $flow['state'], (string) $request->query('state'))) {
            return $this->socialFail($failRoute, $mode === 'link' ? lng('error.social_link') : lng('error.social_login', ['provider' => $label]));
        }

        $code = (string) $request->query('code');
        $profile = $code !== '' ? $service->profileFromCode($code) : null;

        if ($profile === null || empty($profile['user_id'])) {
            return $this->socialFail($failRoute, $mode === 'link' ? lng('error.social_link') : lng('error.social_login', ['provider' => $label]));
        }

        if ($mode === 'link') {
            return $this->linkSocialAccount($service->key(), $profile);
        }

        return $this->socialLogin(
            $request,
            $service->key(),
            $profile,
            $mode === 'register',
            (bool) $flow['agreement'],
            $failRoute
        );
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect(route('login'));
    }

    private function beginOauthFlow(Request $request, SocialOAuthProvider $service, string $mode): RedirectResponse
    {
        $socials = app(SocialAccountService::class);
        $label = $socials->label($service->key());

        if (!$service->configured()) {
            return $this->socialFail($this->oauthModeRoute($mode), $mode === 'link' ? lng('error.social_link') : lng('error.social_login', ['provider' => $label]));
        }

        $agreement = $request->boolean('agreement');

        if ($mode === 'register' && !$agreement) {
            return $this->socialFail($this->oauthModeRoute('register'), lng('error.agreement'));
        }

        $state = Str::random(40);
        $url = $service->authorizeUrl($state);

        if ($url === null) {
            return $this->socialFail($this->oauthModeRoute($mode), $mode === 'link' ? lng('error.social_link') : lng('error.social_login', ['provider' => $label]));
        }

        $request->session()->put(self::OAUTH_SESSION_KEY, [
            'provider'  => $service->key(),
            'state'     => $state,
            'mode'      => $mode,
            'agreement' => $agreement,
        ]);

        return redirect()->away($url);
    }

    private function socialLogin(Request $request, string $provider, array $profile, bool $allowRegister, bool $agreement, ?string $failRoute = null): RedirectResponse
    {
        $socials = app(SocialAccountService::class);
        $label = $socials->label($provider);
        $socialId = (string) $profile['user_id'];
        $email = $socials->normalizeEmail($profile['email'] ?? null);

        $user = $socials->findUser($provider, $socialId, $email);

        if ($user === null) {
            if (!$allowRegister) {
                return $this->socialFail($failRoute, lng('error.social_not_registered', ['provider' => $label]));
            }

            if (!$agreement) {
                return $this->socialFail($failRoute, lng('error.agreement'));
            }

            try {
                $user = $socials->createUser($provider, $socialId, $profile);
            } catch (\Exception $e) {
                return $this->socialFail($failRoute, lng('error.register'));
            }
        } elseif ($email !== null && $socials->isSyntheticEmail($provider, (string) $user->email, $socialId)) {
            $socials->replaceSyntheticEmail($user, $email);
        }

        $socials->linkToUser($user->id, $provider, $socialId);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function linkSocialAccount(string $provider, array $profile): RedirectResponse
    {
        $socials = app(SocialAccountService::class);
        $socialId = (string) $profile['user_id'];
        $user = Auth::user();

        if ($user === null) {
            return redirect()->route('login')->with('error', lng('unauthorized'));
        }

        $ownerId = $socials->bindingOwner($provider, $socialId);

        if ($ownerId !== null && $ownerId !== $user->id) {
            return redirect()->route('settings')->with('error', lng('error.social_link_used', ['provider' => $socials->label($provider)]));
        }

        $socials->linkToUser($user->id, $provider, $socialId);

        return redirect()->route('settings')->with('success', lng('success.social_link'));
    }

    private function oauthModeRoute(string $mode): string
    {
        return match ($mode) {
            'register' => 'register',
            'link'     => 'settings',
            default    => 'login',
        };
    }

    private function socialFail(?string $route, string $message): RedirectResponse
    {
        if ($route !== null) {
            return redirect()->route($route)->with('error', $message);
        }

        return redirect()->back()->with('error', $message);
    }

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
