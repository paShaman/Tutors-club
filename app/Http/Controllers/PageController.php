<?php

namespace App\Http\Controllers;

use App\Page;

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
        $page = Page::where('name', $pageName)
            ->where('active', 1)
            ->firstOrFail();

        $this->title[]  = $page->title;

        $this->data['description']  = $page->description;
        $this->data['keywords']     = $page->keywords;

        return $this->_renderPage($page->name);
    }
}
