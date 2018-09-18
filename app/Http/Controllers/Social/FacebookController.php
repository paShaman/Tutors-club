<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Model\Social;
use App\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;


class FacebookController extends Controller
{
    /**
     * Redirect the user to the FaceBook authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Obtain the user information from FaceBook.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->user();

            $create = [
                'first_name'    => $socialUser->getName(),
                'email'         => $socialUser->getEmail(),
            ];

            if (empty($create['email'])) {
                //пользователь не предоставил доступ к email
                $user = User::has('social', '=', Social::SOCIAL_FACEBOOK)->first();
            } else {
                //проверка на существование пользователя по email
                $user = User::where('email', $create['email'])->first();
            }

            if (is_null($user)) {
                $user = User::create($create);
            } else {
                if (!$user->first_name) {
                    $user->first_name = $create['first_name'];
                    $user->save();
                }
            }

            $socials = $user->social()->get()->toArray();

            if (!in_array(Social::SOCIAL_FACEBOOK, array_column($socials, 'social'))) {
                $social = new Social([
                    'user_id' => $user->id,
                    'social' => Social::SOCIAL_FACEBOOK,
                    'social_id' => $socialUser->getId(),
                ]);

                $user->social()->save($social);
            }

            Auth::loginUsingId($user->id);

            return redirect()->route('home');

        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }
}