<?php

namespace App\Http\Controllers;

use App\Common;
use App\Page;

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

        if ($page->need_auth == 1) {
            $this->middleware('auth');
        }

        $this->title[]  = $page->title;

        $this->data['description']  = $page->description;
        $this->data['keywords']     = $page->keywords;

        return $this->_renderPage($page->name);
    }
}
