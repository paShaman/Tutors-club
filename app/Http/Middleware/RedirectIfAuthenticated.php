<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            if (request()->isXmlHttpRequest()) {
                return response()->json(['message' => lng('only_for_guest')], Response::HTTP_METHOD_NOT_ALLOWED);
            } else {
                return redirect(route('home'));
            }
        }

        return $next($request);
    }
}
