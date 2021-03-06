<?php

namespace App\Http\Controllers;

use App\Form;
use App\Model\Page;
use App\Model\Social;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except([
            'login',
            'register',
            'passwordRecovery',
            'policy'
        ]);

        parent::__construct();
    }
    /**
     * рендерим страницу
     *
     * @param string $pageName
     * @return \Illuminate\View\View
     */
    public function page($pageName = Page::PAGE_DEFAULT)
    {
        return $this->_renderPage($pageName);
    }

    /**
     * страница авторизации
     *
     * @return \Illuminate\View\View
     */
    public function login()
    {
        return $this->_renderPage('login');
    }

    /**
     * страница регистрации
     *
     * @return \Illuminate\View\View
     */
    public function register()
    {
        $this->_initReCaptcha();

        return $this->_renderPage('register');
    }

    /**
     * страница восстановления пароля
     *
     * @return \Illuminate\View\View
     */
    public function passwordRecovery()
    {
        return $this->_renderPage('password-recovery');
    }

    /**
     * страница настроек
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $socialNetworks = [
            Social::SOCIAL_VKONTAKTE    => [],
            Social::SOCIAL_FACEBOOK     => [],
            Social::SOCIAL_GOOGLE       => [],
        ];

        $userSocialNetworks = Auth::user()->social()->get();

        foreach ($userSocialNetworks as $socialNetwork) {
            $socialNetworks[$socialNetwork->social] = $socialNetwork->social_id;
        }

        $this->tpl['socialNetworks'] = $socialNetworks;

        return $this->_renderPage('settings');
    }

    /**
     * страница учеников
     *
     * @return \Illuminate\View\View
     */
    public function students()
    {
        $students = Auth::user()->students()->get()->toArray();

        $this->tpl['students'] = $students;
        $this->tpl['modalAddStudent'] = Form::buildModal('add-student', lng('add_student'));

        return $this->_renderPage('students');
    }
}
