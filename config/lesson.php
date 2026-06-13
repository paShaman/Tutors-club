<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Lesson Settings
    |--------------------------------------------------------------------------
    |
    | These values are used as defaults when creating a new lesson.
    |
    */
    'default_price' => (int) env('LESSON_DEFAULT_PRICE', 3000),
    'default_duration' => (int) env('LESSON_DEFAULT_DURATION', 60),
];