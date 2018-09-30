<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class PanelController extends Controller
{
    /**
     * рендерим страницу администратора
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return $this->_renderPage('admin.panel');
    }
}
