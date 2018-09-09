<?php

namespace App\Http\Controllers;

use App\Common;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $page;
    public $title       = ['Клуб репетиторов'];
    public $template    = [];
    public $styles      = [];
    public $scripts     = [];

    public function __construct()
    {
        $this->styles = [
            '/assets/plugins/bootstrap/bootstrap.css',
            Common::getAssetsPath() . 'css/google-sans.css',
            Common::getAssetsPath() . 'css/style.css',
        ];
    }

    public function render()
    {
        $data = array_merge(
            [
                'title'     => implode(" - ", $this->title),
                'styles'    => $this->styles,
                'scripts'   => $this->scripts,
            ],
            $this->template
        );

        return view('pages.' . $this->page, $data);
    }
}
