<?php

namespace App\Http\Controllers;

use App\Image;
use App\Services\SocialAccountService;
use App\Services\VkIdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * редактирование настроек
     */
    public function settings()
    {
        $post = request()->post();
        $user = Auth::user();

        if (array_key_exists('first_name', $post)) {
            $user->first_name = trim((string) $post['first_name']);
        }
        if (array_key_exists('last_name', $post)) {
            $user->last_name = trim((string) $post['last_name']);
        }
        if (array_key_exists('middle_name', $post)) {
            $user->middle_name = trim((string) $post['middle_name']);
        }

        $oldAvatar = $user->avatar;

        if (array_key_exists('avatar', $post)) {
            $user->avatar = !empty($post['avatar']) ? (string) $post['avatar'] : null;
        }

        $user->save();

        if ($oldAvatar !== $user->avatar) {
            Image::deleteStoredAvatar($oldAvatar);
        }

        return back()->with('success', lng('success.settings'));
    }

    /**
     * Установка или смена пароля.
     */
    public function password(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'password'              => ['required'],
            'password_confirmation' => ['required', 'same:password'],
        ];

        $messages = [
            'password.required'              => 'Введите новый пароль',
            'password_confirmation.required' => 'Повторите новый пароль',
            'password_confirmation.same'     => 'Пароли не совпадают',
        ];

        if ((string) $user->password !== '') {
            $rules['current_password'] = ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check((string) $value, (string) $user->password)) {
                    $fail(lng('error.password_current'));
                }
            }];
            $messages['current_password.required'] = 'Укажите текущий пароль';
        }

        $request->validate($rules, $messages);

        $user->password = Hash::make((string) $request->input('password'));
        $user->save();

        return redirect()->back()->with('success', lng('success.password'));
    }

    /**
     * Привязка VK ID к текущему аккаунту.
     */
    public function socialLink(Request $request): RedirectResponse
    {
        $accessToken = (string) $request->input('access_token');

        if ($accessToken === '') {
            return redirect()->back()->with('error', lng('error.social_link'));
        }

        $profile = app(VkIdService::class)->fetchUserInfo($accessToken);

        if ($profile === null || empty($profile['user_id'])) {
            return redirect()->back()->with('error', lng('error.social_link'));
        }

        $social = VkIdService::SOCIAL_VKONTAKTE;
        $socialId = (string) $profile['user_id'];
        $ownerId = app(SocialAccountService::class)->bindingOwner($social, $socialId);

        if ($ownerId !== null && $ownerId !== Auth::id()) {
            return redirect()->back()->with('error', lng('error.social_link_used', ['provider' => app(SocialAccountService::class)->label($social)]));
        }

        app(SocialAccountService::class)->linkToUser(Auth::id(), $social, $socialId);

        return redirect()->back()->with('success', lng('success.social_link'));
    }

    /**
     * Отвязка соцсети от текущего аккаунта.
     */
    public function socialUnlink(Request $request): RedirectResponse
    {
        $social = (string) $request->input('provider');

        if (!app(SocialAccountService::class)->supports($social)) {
            return redirect()->back()->with('error', lng('error.social_unlink'));
        }

        $user = Auth::user();
        $userId = Auth::id();

        // Нельзя отвязать единственный способ входа (если нет пароля).
        $otherLoginExists = DB::table('users_social')
            ->where('user_id', $userId)
            ->where('social', '!=', $social)
            ->exists();

        if (!$otherLoginExists && (string) $user->password === '') {
            return redirect()->back()->with('error', lng('error.social_unlink_only'));
        }

        DB::table('users_social')
            ->where('social', $social)
            ->where('user_id', $userId)
            ->delete();

        return redirect()->back()->with('success', lng('success.social_unlink'));
    }
}
