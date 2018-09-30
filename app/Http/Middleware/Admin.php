<?php

namespace App\Http\Middleware;

use App\Access;
use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * проверка на админа
     *
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check() || Auth::id() != Access::ADMIN_USER_ID) {
            return redirect(route('home'));
        }

        return $next($request);
    }
}
