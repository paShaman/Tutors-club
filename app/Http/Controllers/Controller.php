<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $page;
    public $title = ['Tutors club'];

    public function render($data)
    {
        return view('pages.' . $this->page, $data);
    }
}
