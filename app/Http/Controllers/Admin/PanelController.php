<?php

namespace App\Http\Controllers\Admin;

use App\Form;
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
        $this->_initJsGrid();

        $this->tpl['modalAdminAddPayment'] = Form::buildModal('admin.add-payment', lng('add_payment'));

        return $this->_renderPage('admin.panel');
    }
}
