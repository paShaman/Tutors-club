<?php

namespace App\Http\Controllers;

use App\Message;
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

        $user->first_name = $post['first_name'] ?? $user->first_name;
        $user->last_name = $post['last_name'] ?? $user->last_name;
        $user->middle_name = $post['middle_name'] ?? $user->middle_name;

        $user->save();

        return $this->_resultSuccess(lng('success.settings'));
    }
}