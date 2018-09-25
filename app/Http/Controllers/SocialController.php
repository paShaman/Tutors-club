<?php

namespace App\Http\Controllers;

use App\Common;
use App\Model\Social;
use App\Model\User;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManagerStatic as Image;


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

            if (empty($socialUser['email'])) {
                //пользователь не предоставил доступ к email
                $user = User::whereHas('social', function ($query, $provider) {
                    $query->where('social', $provider);
                })->first();
            } else {
                //проверка на существование пользователя по email
                $user = User::where('email', $socialUser['email'])->first();
            }

            if (is_null($user)) {
                $user = User::create($socialUser);
            } else {
                $imgUrl = $socialUser['avatar']['url'] ?? '';

                if ($imgUrl) {
                    $imgUrl = preg_replace("/(.*)\?(.*)/", '$1', $imgUrl);

                    $avatar = Image::make($imgUrl);

                    $avatar->setFileInfoFromPath($imgUrl);

                    $avatar->fit(300);

                    list($filePath, $dir) = Common::generateFilePath(
                        !empty($avatar->filename) ? $avatar->filename : ($socialUser['avatar']['filename'] ?? uniqid()),
                        !empty($avatar->extension) ? $avatar->extension : ($socialUser['avatar']['extension'] ?? 'jpg'),
                        true)
                    ;

                    $avatar = $avatar->save($filePath);

                    $user->avatar = $dir . $avatar->filename . '.' . $avatar->extension;
                }

                $user->first_name = !empty($socialUser['first_name']) ? $socialUser['first_name'] : $user->first_name;
                $user->last_name = !empty($socialUser['last_name']) ? $socialUser['last_name'] : $user->last_name;
                $user->save();
            }

            $socials = $user->social()->get()->toArray();

            if (!in_array($provider, array_column($socials, 'social'))) {
                $social = new Social([
                    'user_id' => $user->id,
                    'social' => $provider,
                    'social_id' => $socialUser['id'],
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