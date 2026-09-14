<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class UiKitController extends Controller
{
    /**
     * Служебный справочник по UI-киту (доступ только у роли admin).
     */
    public function index(): Response
    {
        return Inertia::render('UiKit');
    }
}
