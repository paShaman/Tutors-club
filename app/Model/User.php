<?php

namespace App\Model;

use App\Access;
use App\VerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use \Venturecraft\Revisionable\RevisionableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'first_name', 'last_name', 'middle_name', 'date_agree', 'avatar', 'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * Get the social connects for the user.
     */
    public function social()
    {
        return $this->hasMany('App\Model\Social');
    }

    /**
     * Get the senders connects for the user.
     */
    public function senders()
    {
        return $this->hasMany('App\Model\Sender');
    }

    /**
     * get protected param
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * отключаем социальную сеть
     *
     * @param $social
     * @return bool
     */
    public function socialDisconnect($social)
    {
        try {
            if (empty($social)) {
                throw new \Exception('empty_params');
            }

            $socialConnected = $this->social()->where('social', $social);

            if (!$socialConnected->first()) {
                throw new \Exception('social_not_connected');
            }

            $socialConnected->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * генерил урл для авторизации по хешу
     *
     * @return string
     */
    public function generateUrlForForceLogin()
    {
        return URL::signedRoute('auth', ['user' => $this->id]);
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }

    /**
     * проверка на админа
     */
    public function isAdmin()
    {
        return $this->id == Access::ADMIN_USER_ID;
    }
}
