<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Model\Role;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * Служебные страницы (/admin/*) доступны только пользователям с ролью admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole(Role::ADMIN)) {
            return Inertia::render('Error', ['status' => 403])
                ->toResponse($request)
                ->setStatusCode(403);
        }

        return $next($request);
    }
}
