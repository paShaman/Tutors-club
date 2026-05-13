<?php

namespace App\Http\Controllers;

use App\Common;
use App\Model\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
        ];

        $post = $request->post();

        $request->validate($rules);

        if (empty(Common::checkRecaptcha($post))) {
            //return redirect()->back()->with('error', lng('error.recaptcha'));
        }

        $existing = User::where('email', $post['email'])->first();
        if (!empty($existing)) {
            return redirect()->back()->with('error', lng('duplicate_email'));
        }

        $user = new User();
        $user->email       = $post['email'];
        $user->password    = Hash::make($post['password']);
        $user->first_name  = $post['first_name'] ?? '';
        $user->last_name   = $post['last_name'] ?? '';
        $user->middle_name = $post['middle_name'] ?? '';
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

        if (Auth::attempt($request->only('email', 'password'), true)) {
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
}
