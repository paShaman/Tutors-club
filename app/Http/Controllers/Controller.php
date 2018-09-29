<?php

namespace App\Http\Controllers;

use App\Common;
use App\Model\Page;
use App\Notification;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $title       = [];
    public $tpl         = [];
    public $styles      = [];
    public $scripts     = [];

    public function __construct()
    {
        app('translator')->setLocale('ru');

        if (request()->isXmlHttpRequest()) {
            //ajax
        } else {
            $this->title[] = lng('title');

            $this->_initFilesBefore();
        }
    }

    /**
     * базовые скрипты и стили
     */
    protected function _initFilesBefore()
    {
        $this->styles = [
            '/assets/plugins/bootstrap/scss/bootstrap.css',
            '/assets/plugins/jGrowl/less/jgrowl.css',
            '/assets/plugins/btnWaves/btnWaves.css',
            Common::getAssetsPath() . 'css/google-sans.css',
            Common::getAssetsPath() . 'css/style.css',
        ];

        $this->scripts = [
            '/assets/plugins/jquery-3.3.1.min.js',
            '/assets/plugins/bootstrap/bootstrap.min.js',
            '/assets/plugins/jGrowl/jquery.jgrowl.min.js',
            '/assets/plugins/fontawesome/js/all.min.js',
            '/assets/plugins/btnWaves/btnWaves.js',
            Common::getAssetsPath() . 'js/functions.js',
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
    protected function _renderPage($pageName)
    {
        $page = Page::where('name', $pageName)
            ->where('active', 1)
            ->firstOrFail();

        $this->title[]  = $page->title;

        $this->tpl['page']  = $page->toArray();

        $data = array_merge(
            [
                'titleFull'     => implode(" - ", array_reverse($this->title)),
                'styles'        => $this->styles,
                'scripts'       => $this->scripts,
                'localization'  => Lang::get('js'),
                'user'          => Auth::user(),
                'userId'        => Auth::id(),
                'messages'      => Notification::collectNotifications()
            ],
            $this->tpl
        );

        return $this->_render('pages.' . $pageName, $data);
    }

    /**
     * response as json
     *
     * @param bool $success
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function _resultJson($success = true, $data = [])
    {
        return response()->json([
            'success'   => $success,
            'data'      => $data,
            'messages'  => Notification::collectNotifications()
        ]);
    }

    protected function _resultSuccess($message = '')
    {
        return $this->_resultJson(true, $message);
    }

    protected function _resultError($message = '')
    {
        if ($message instanceof \Illuminate\Validation\Validator) {
            $errors = array_combine($message->errors()->keys(), $message->errors()->all());
        } else {
            $errors = $message;
        }

        return $this->_resultJson(false, $errors);
    }

    /**
     * init Google ReCaptcha
     */
    protected function _initReCaptcha()
    {
        $this->scripts[] = 'https://www.google.com/recaptcha/api.js';
    }
}
