<?php

namespace App\Http\Controllers;

use App\Page;

class PageController extends Controller
{
    /**
     * рендерим страницу
     *
     * @param string $page
     * @return \Illuminate\View\View
     */
    public function page($page = Page::PAGE_DEFAULT)
    {
        Page::where('name', $page)
            ->where('active', 1)
            ->firstOrFail();

        $this->title[] = 'Личный кабинет';
        $this->page = $page;

        return $this->render();
    }
}
