<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $page;
    public $title = ['Клуб репетиторов'];
    public $template = [];

    public function render()
    {
        $data = array_merge(
            [
                'title'     => implode(" - ", $this->title)
            ],
            $this->template
        );

        return view('pages.' . $this->page, $data);
    }
}
