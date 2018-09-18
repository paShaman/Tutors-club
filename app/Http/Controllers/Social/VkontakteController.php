<?php

namespace App\Http\Controllers\Social;

use App\Common;
use App\Http\Controllers\Controller;
use App\Model\Social;
use App\User;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManagerStatic as Image;
use Laravel\Socialite\Facades\Socialite;


class VkontakteController extends Controller
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
                'first_name'    => $socialUser->user['first_name'],
                'last_name'     => $socialUser->user['last_name'],
                'email'         => $socialUser->accessTokenResponseBody['email'],
            ];

            if (empty($create['email'])) {
                //пользователь не предоставил доступ к email
                $user = User::has('social', '=', Social::SOCIAL_VKONTAKTE)->first();
            } else {
                //проверка на существование пользователя по email
                $user = User::where('email', $create['email'])->first();
            }

            if (is_null($user)) {
                $user = User::create($create);
            } else {
                $imgUrl = $socialUser->getAvatar();

                if ($imgUrl) {
                    $imgUrl = preg_replace("/(.*)\?(.*)/", '$1', $imgUrl);

                    $avatar = Image::make($imgUrl);

                    $avatar->setFileInfoFromPath($imgUrl);

                    // resize only the width of the image
                    $avatar->resize(300, null);

                    list($filePath, $dir) = Common::generateFilePath($avatar->filename, $avatar->extension, true);

                    $avatar = $avatar->save($filePath);

                    $user->avatar = $dir . $avatar->filename . '.' . $avatar->extension;
                }

                $user->first_name = !empty($create['first_name']) ? $create['first_name'] : $user->first_name;
                $user->last_name = !empty($create['last_name']) ? $create['last_name'] : $user->last_name;
                $user->save();
            }

            $socials = $user->social()->get()->toArray();

            if (!in_array(Social::SOCIAL_VKONTAKTE, array_column($socials, 'social'))) {
                $social = new Social([
                    'user_id' => $user->id,
                    'social' => Social::SOCIAL_VKONTAKTE,
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