<?php

namespace App\Http\Controllers;

use App\Common;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $title       = ['Клуб репетиторов'];
    public $data        = [];
    public $styles      = [];
    public $scripts     = [];

    public function __construct()
    {
        $this->styles = [
            '/assets/plugins/bootstrap/scss/bootstrap.css',
            Common::getAssetsPath() . 'css/google-sans.css',
            Common::getAssetsPath() . 'css/style.css',
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
                'title'     => implode(" - ", $this->title),
                'styles'    => $this->styles,
                'scripts'   => $this->scripts,
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
     * @return Response
     */
    protected function _resultJson($success = true, $data = [], $message = '')
    {
        $status = $success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new Response(json_encode([
            'success'   => $success,
            'data'      => $data,
            'message'   => $message,
        ]), $status);
    }

    protected function _resultSuccess($message = '')
    {
        return $this->_resultJson(true, [], $message);
    }

    protected function _resultError($message = '')
    {
        return $this->_resultJson(false, [], $message);
    }
}
