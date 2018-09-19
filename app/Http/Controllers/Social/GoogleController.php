<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\SocialController;
use App\Model\Social;
use Laravel\Socialite\Facades\Socialite;


class GoogleController extends SocialController
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            $create = [
                'first_name'    => $socialUser->user['name']['givenName'] ?? $socialUser->getName(),
                'last_name'     => $socialUser->user['name']['familyName'] ?? '',
                'email'         => $socialUser->getEmail(),
                'avatar'        => ['url' => $socialUser->getAvatar()]
            ];

            return $this->_handleProviderCallback(Social::SOCIAL_GOOGLE, $create);

        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }
}