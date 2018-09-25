<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\SocialController;
use App\Model\Social;
use Laravel\Socialite\Facades\Socialite;


class VkontakteController extends SocialController
{
    /**
     * Redirect the user to the Vkontakte authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('vkontakte')->redirect();
    }

    /**
     * Obtain the user information from Vkontakte.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $socialUser = Socialite::driver('vkontakte')->user();

            $create = [
                'id'            => $socialUser->getId(),
                'first_name'    => $socialUser->user['first_name'],
                'last_name'     => $socialUser->user['last_name'],
                'email'         => $socialUser->accessTokenResponseBody['email'],
                'avatar'        => $socialUser->getAvatar()
            ];

            return $this->_handleProviderCallback(Social::SOCIAL_VKONTAKTE, $create);

        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }
}