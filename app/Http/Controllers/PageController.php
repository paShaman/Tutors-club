<?php

namespace App\Http\Controllers;

use App\Common;
use App\Page;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->title[] = lng('title');

        $this->styles = [
            '/assets/plugins/bootstrap/scss/bootstrap.css',
            '/assets/plugins/jGrowl/less/jgrowl.css',
            Common::getAssetsPath() . 'css/google-sans.css',
            Common::getAssetsPath() . 'css/style.css',
        ];

        $this->scripts = [
            '/assets/plugins/jquery-3.3.1.min.js',
            '/assets/plugins/jGrowl/jquery.jgrowl.min.js',
            '/assets/plugins/fontawesome/js/all.min.js',
            Common::getAssetsPath() . 'js/main.js',
        ];

    }

    /**
     * рендерим страницу
     *
     * @param string $pageName
     * @return \Illuminate\View\View
     */
    public function page($pageName = Page::PAGE_DEFAULT)
    {
        $page = Page::where('name', $pageName)
            ->where('active', 1)
            ->firstOrFail();

        //без middleware проверяем авторизацию и доступность страницы гостю
        if ($page->need_auth == Page::NEED_AUTH && !Auth::check()) {
            return redirect(route('login'));
        }
        if ($page->need_auth == Page::NO_NEED_AUTH && Auth::check()) {
            return redirect(route('home'));
        }

        $this->title[]  = $page->title;

        $this->data['description']  = $page->description;
        $this->data['keywords']     = $page->keywords;

        $pageFuncName = '_page' . studly_case($page->name);

        if (method_exists($this, $pageFuncName)) {
            $this->$pageFuncName();
        }

        return $this->_renderPage($page->name);
    }

    /**
     * доп функции для страницы регистрации
     */
    protected function _pageRegister()
    {
        $this->_initReCaptcha();
    }
}
