<?php

namespace App\Http\Controllers;

use App\Model\User;
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
