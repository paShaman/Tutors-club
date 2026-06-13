<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except([
            'login',
            'register',
        ]);
    }

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
        return Inertia::render('Settings');
    }
}