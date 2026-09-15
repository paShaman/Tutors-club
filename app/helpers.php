<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

if (!function_exists('lng')) {
    /**
     * Get a localized string from messages.php, falling back to admin.php
     * (admin-only strings live there and are not shared with regular users).
     *
     * @param  string  $key    Dot-notation key (e.g. 'error.register', 'title')
     * @param  array   $replace Parameters to replace in the translation string
     * @param  string|null $locale Force a specific locale
     * @return string
     */
    function lng(string $key, array $replace = [], ?string $locale = null): string
    {
        $value = Lang::get('messages.' . $key, $replace, $locale);

        // Админские строки живут в admin.php и запасным источником идут только тут
        if ($value === 'messages.' . $key) {
            $adminValue = Lang::get('admin.' . $key, $replace, $locale);

            if ($adminValue !== 'admin.' . $key) {
                return $adminValue;
            }
        }

        return $value;
    }
}