<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\SocialController;
use App\Model\Social;
use Laravel\Socialite\Facades\Socialite;


class FacebookController extends SocialController
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
                'avatar'        => [
                    'url'       => $socialUser->getAvatar(),
                    'filename'  => $socialUser->getId(),
                    'extension' => 'jpg'
                ]
            ];

             return $this->_handleProviderCallback(Social::SOCIAL_FACEBOOK, $create);

        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }
}