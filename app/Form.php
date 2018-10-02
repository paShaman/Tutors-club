<?php

namespace App;

class Form
{
    /**
     * рендерим форму
     *
     * @param $viewName
     * @param $title
     * @param array $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public static function buildModal($viewName, $title, $data = [])
    {
        $data['modalId'] = 'modal' . studly_case(str_replace(['.', '/'], '-', $viewName));
        $data['modalTitle'] = $title;

        return view('forms.' . $viewName, $data);
    }
}
