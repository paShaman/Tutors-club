<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

if (!function_exists('lng')) {
    /**
     * Get a localized string from the messages.php translation file.
     *
     * @param  string  $key    Dot-notation key (e.g. 'error.register', 'title')
     * @param  array   $replace Parameters to replace in the translation string
     * @param  string|null $locale Force a specific locale
     * @return string
     */
    function lng(string $key, array $replace = [], ?string $locale = null): string
    {
        return Lang::get('messages.' . $key, $replace, $locale);
    }
}