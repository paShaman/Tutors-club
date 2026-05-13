<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Social;
use Illuminate\Support\Facades\Auth;
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
        $socialNetworks = [
            Social::SOCIAL_GOOGLE    => [],
        ];

        $userSocialNetworks = Auth::user()->social()->get();

        foreach ($userSocialNetworks as $socialNetwork) {
            $socialNetworks[$socialNetwork->social] = $socialNetwork->social_id;
        }

        return Inertia::render('Settings', [
            'socialNetworks' => $socialNetworks,
        ]);
    }
}