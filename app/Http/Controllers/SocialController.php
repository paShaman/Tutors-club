<?php

namespace App\Http\Controllers;

use App\Image;
use App\Notification;
use App\Model\Social;
use App\Model\User;
use Illuminate\Support\Facades\Auth;


class SocialController extends Controller
{
    /**
     * Obtain the user information from social provided.
     *
     * @return \Illuminate\Http\Response
     */
    protected function _handleProviderCallback($provider, $socialUser)
    {
        try {
            if (empty($provider) || empty($socialUser)) {
                throw new \Exception('empty_params');
            }

            $user = Auth::user();

            //сначала проверяем может такая привязка уже есть
            $userBySocial = User::whereHas('social', function ($query) use ($provider, $socialUser) {
                $query
                    ->where('social', $provider)
                    ->where('social_id', $socialUser['id'])
                ;
            })->first();

            //проверка на существование пользователя по email
            if (!empty($socialUser['email'])) {
                $userByEmail = User::where('email', $socialUser['email'])->first();
            }

            if (!empty($user)) {
                //текущий пользователь и пользователь по социальной сети не совпадают
                if (!empty($userBySocial) && $user->getId() != $userBySocial->getId()) {
                    Notification::put(lng('error.social_user_exists'));

                    throw new \Exception('social_user_exists');
                }
            } else if (!empty($userBySocial)) {
                $user = $userBySocial;
            }

            if (!empty($user)) {
                //текущий пользователь и пользователь по email и не совпадают
                if (!empty($userByEmail) && $user->getId() != $userByEmail->getId()) {
                    Notification::put(lng('error.email_user_exists'));

                    throw new \Exception('email_user_exists');
                }
            } else if (!empty($userByEmail)) {
                $user = $userByEmail;
            }

            //avatar
            if (!empty($socialUser['avatar'])) {
                $socialUser['avatar'] = Image::createImgUrl($socialUser['avatar']['url'] ?? $socialUser['avatar'], [
                    'filename'  => $socialUser['avatar']['filename'] ?? null,
                    'extension' => $socialUser['avatar']['extension'] ?? null,
                    'fit'       => 300
                ]);
            }

            if (is_null($user)) {
                //новый пользователь
                $user = User::create($socialUser);
            } else {
                $user->email        = empty($user->email) && !empty($socialUser['email']) ? $socialUser['email'] : $user->email;
                $user->avatar       = empty($user->avatar) && !empty($socialUser['avatar']) ? $socialUser['avatar'] : $user->avatar;
                $user->first_name   = empty($user->first_name) && !empty($socialUser['first_name']) ? $socialUser['first_name'] : $user->first_name;
                $user->last_name    = empty($user->last_name) && !empty($socialUser['last_name']) ? $socialUser['last_name'] : $user->last_name;
                $user->save();
            }

            //привязки не было, надо создать
            if (empty($userBySocial)) {
                $exists = $user->social()->where('social', $provider)->first();

                if (!$exists) {
                    $social = new Social([
                        'user_id' => $user->id,
                        'social' => $provider,
                        'social_id' => $socialUser['id'],
                    ]);

                    $user->social()->save($social);
                }
            }

            //если залогинен то пришел из настроек
            if (Auth::check()) {
                return redirect()->route('settings');
            } else {
                Auth::loginUsingId($user->id);

                return redirect()->route('home');
            }
        } catch (\Exception $e) {
            if (Auth::check()) {
                return redirect()->route('settings');
            } else {
                return redirect()->route('login');
            }
        }
    }

    /**
     * отключаем соц сеть
     */
    public function disconnect()
    {
        $social = request()->post('social');
        $user = Auth::user();

        $result = $user->socialDisconnect($social);

        if ($result) {
            return $this->_resultSuccess(lng('success.social_disconnect'));
        }
        return $this->_resultError(lng('error.social_disconnect'));
    }
}