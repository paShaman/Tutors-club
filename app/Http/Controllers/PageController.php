<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PageController extends Controller
{
    /**
     * Login page.
     */
    public function login(): Response
    {
        return Inertia::render('Login');
    }

    /**
     * Register page.
     */
    public function register(): Response
    {
        return Inertia::render('Register');
    }

    /**
     * Settings page.
     */
    public function settings(): Response
    {
        $socials = [];

        if ($user = Auth::user()) {
            $socials = DB::table('users_social')
                ->where('user_id', $user->id)
                ->orderBy('social')
                ->get()
                ->map(function ($row) {
                    return [
                        'provider'   => $row->social,
                        'social_id'  => $row->social_id,
                        'created_at' => $row->created_at,
                    ];
                })
                ->values()
                ->all();
        }

        return Inertia::render('Settings', ['socials' => $socials]);
    }
}