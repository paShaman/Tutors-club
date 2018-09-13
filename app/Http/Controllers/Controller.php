<?php

namespace App\Http\Controllers;

use App\Common;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Lang;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $title       = [];
    public $data        = [];
    public $styles      = [];
    public $scripts     = [];

    public function __construct()
    {
        //$this->middleware('auth');

        app('translator')->setLocale('ru');

        $this->title[] = lng('title');

        $this->styles = [
            '/assets/plugins/bootstrap/scss/bootstrap.css',
            '/assets/plugins/jGrowl/less/jgrowl.css',
            Common::getAssetsPath() . 'css/google-sans.css',
            Common::getAssetsPath() . 'css/style.css',
        ];

        $this->scripts = [
            '/assets/plugins/jquery-3.3.1.min.js',
            '/assets/plugins/jGrowl/jquery.jgrowl.min.js',
            '/assets/plugins/fontawesome/js/all.min.js',
            Common::getAssetsPath() . 'js/main.js',
        ];
    }

    /**
     * рендер блока
     *
     * @param $view
     * @param $data
     * @return \Illuminate\View\View
     */
    protected function _render($view, $data)
    {
        return view($view, $data);
    }

    /**
     * рендер страницы
     *
     * @return \Illuminate\View\View
     */
    protected function _renderPage($page)
    {
        $data = array_merge(
            [
                'title'         => implode(" - ", $this->title),
                'styles'        => $this->styles,
                'scripts'       => $this->scripts,
                'localization'  => Lang::get('js')
            ],
            $this->data
        );

        return $this->_render('pages.' . $page, $data);
    }

    /**
     * response as json
     *
     * @param bool $success
     * @param array $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function _resultJson($success = true, $data = [], $message = '')
    {

        return response()->json([
            'success'   => $success,
            'data'      => $data,
            'message'   => $message,
        ]);
    }

    protected function _resultSuccess($message = '')
    {
        return $this->_resultJson(true, [], $message);
    }

    protected function _resultError($message = '')
    {
        if ($message instanceof \Illuminate\Validation\Validator) {
            $errors = array_combine($message->errors()->keys(), $message->errors()->all());

            return $this->_resultJson(false, $errors);
        }
        return $this->_resultJson(false, [], $message);
    }
}
