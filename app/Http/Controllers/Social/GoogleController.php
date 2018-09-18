<?php

namespace App\Http\Controllers\Social;

use App\Common;
use App\Http\Controllers\Controller;
use App\Model\Social;
use App\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Intervention\Image\ImageManagerStatic as Image;


class GoogleController extends Controller
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
            ];

            if (empty($create['email'])) {
                //пользователь не предоставил доступ к email
                $user = User::has('social', '=', Social::SOCIAL_GOOGLE)->first();
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

                    $avatar->fit(300);

                    list($filePath, $dir) = Common::generateFilePath($avatar->filename, $avatar->extension, true);

                    $avatar = $avatar->save($filePath);

                    $user->avatar = $dir . $avatar->filename . '.' . $avatar->extension;
                }

                $user->first_name = !empty($create['first_name']) ? $create['first_name'] : $user->first_name;
                $user->last_name = !empty($create['last_name']) ? $create['last_name'] : $user->last_name;
                $user->save();
            }

            $socials = $user->social()->get()->toArray();

            if (!in_array(Social::SOCIAL_GOOGLE, array_column($socials, 'social'))) {
                $social = new Social([
                    'user_id' => $user->id,
                    'social' => Social::SOCIAL_GOOGLE,
                    'social_id' => $socialUser->getId(),
                ]);

                $user->social()->save($social);
            }

            Auth::loginUsingId($user->id);

            return redirect()->route('home');

        } catch (\Exception $e) {
            echo $e->getMessage();die;
            return redirect()->route('login');
        }
    }
}