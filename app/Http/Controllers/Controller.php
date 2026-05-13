<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Notification;
use App\Model\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Legacy render for Blade-based controllers (kept for backward compatibility).
     */
    protected function _renderPage(string $pageName): \Illuminate\View\View
    {
        $page = Page::where('name', $pageName)
            ->where('is_active', 1)
            ->firstOrFail();

        $titleFull = lng('title') . ' — ' . $page->title;

        return view('pages.' . $pageName, [
            'titleFull'    => $titleFull,
            'page'         => $page->toArray(),
            'user'         => Auth::user(),
            'userId'       => Auth::id(),
            'messages'     => Notification::collectNotifications(),
            'localization' => Lang::get('js'),
        ]);
    }

    /**
     * Response as JSON (used by AJAX endpoints).
     */
    protected function _resultJson(bool $success = true, mixed $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success'  => $success,
            'data'     => $data,
            'messages' => Notification::collectNotifications(),
        ]);
    }

    protected function _resultSuccess(string $message = ''): \Illuminate\Http\JsonResponse
    {
        return $this->_resultJson(true, $message);
    }

    protected function _resultError(mixed $message = ''): \Illuminate\Http\JsonResponse
    {
        if ($message instanceof \Illuminate\Validation\Validator) {
            $errors = array_combine($message->errors()->keys(), $message->errors()->all());
        } else {
            $errors = $message;
        }

        return $this->_resultJson(false, $errors);
    }
}