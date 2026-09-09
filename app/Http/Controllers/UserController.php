<?php

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Support\Facades\Auth;


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
}