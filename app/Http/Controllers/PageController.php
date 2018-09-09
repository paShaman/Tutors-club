<?php

namespace App\Http\Controllers;

use App\Page;

class PageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function page($page = Page::PAGE_DEFAULT)
    {
        /*Page::where('name', $page)
            ->where('active', 1)
            ->firstOrFail();

        $this->title[] = 'Личный кабинет';
        $this->page = $page;*/

        return $this->render();
    }
}
