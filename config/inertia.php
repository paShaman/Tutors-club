<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render the initial visits made to your application's pages.
    |
    | You can specify a custom SSR bundle path, or omit it to let Inertia
    | try to automatically detect it for you.
    |
    | Do note that enabling these options will NOT automatically make SSR work,
    | as a separate SSR service needs to be available. To learn more, please
    | visit https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => false,
        'url' => 'http://127.0.0.1:13714',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Set `ensure_pages_exist` to true if you want to enforce that Inertia page
    | components exist on disk when rendering a page. This is useful for
    | catching missing or misnamed components.
    |
    | The `paths` and `extensions` options define where to look for page
    | components and which file extensions to consider.
    |
    */

    'pages' => [
        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'vue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | When using `assertInertia`, the assertion attempts to locate the
    | component as a file relative to the `pages.paths` AND with any of the
    | `pages.extensions` specified above.
    |
    */

    'testing' => [
        'ensure_pages_exist' => true,
    ],

];
