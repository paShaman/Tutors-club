<?php

namespace App\Http\Controllers;

use App\Page;
use Illuminate\Support\Facades\Cache;


class PageController extends Controller
{
    public function page($page = Page::PAGE_DEFAULT)
    {
        Page::where('name', $page)
            ->where('active', 1)
            ->firstOrFail();

        $this->title[] = 'Личный кабинет';
        $this->page = $page;
        $this->template['cache'] = Cache::get('test');

        return $this->render();
    }
}
