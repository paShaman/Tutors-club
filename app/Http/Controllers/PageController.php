<?php

namespace App\Http\Controllers;

use App\Model\Page;

class PageController extends Controller
{
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

        return $this->_renderPage('login');
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
}
