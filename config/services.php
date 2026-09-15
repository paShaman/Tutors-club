<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yandex_smartcaptcha' => [
        'site_key'   => env('SMARTCAPTCHA_SITE_KEY'),
        'server_key' => env('SMARTCAPTCHA_SERVER_KEY'),
    ],

    'vkid' => [
        'app_id'       => env('VK_ID_APP_ID'),
        'redirect_url' => env('VK_ID_REDIRECT_URL'),
        'base_url'     => env('VK_ID_BASE_URL', 'https://id.vk.com'),
    ],

    'yandex' => [
        'client_id'     => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect_uri'  => env('YANDEX_REDIRECT_URI'),
    ],

    'yandex_metrika' => [
        'id' => env('YANDEX_METRIKA_ID'),
    ],

    'telegram' => [
        'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'        => env('TELEGRAM_CHAT_ID'),
        'bot_username'   => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // Прокси для исходящих запросов: с части хостингов api.telegram.org
        // недоступен. Формат: http://user:pass@host:port или socks5h://host:port.
        'proxy'          => env('TELEGRAM_PROXY'),
    ],

    // Отдельный бот управления учениками (личный кабинет прямо в Telegram).
    'telegram_students' => [
        'bot_token'      => env('TELEGRAM_STUDENTS_BOT_TOKEN'),
        'bot_username'   => env('TELEGRAM_STUDENTS_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_STUDENTS_WEBHOOK_SECRET'),
        'proxy'          => env('TELEGRAM_STUDENTS_PROXY', env('TELEGRAM_PROXY')),
    ],

    'max' => [
        'bot_token'      => env('MAX_BOT_TOKEN'),
        'owner_id'       => env('MAX_OWNER_ID'),
        'bot_username'   => env('MAX_BOT_USERNAME'),
        'webhook_secret' => env('MAX_WEBHOOK_SECRET'),
        'proxy'          => env('MAX_PROXY'),
    ],

    // Основной бот обратной связи: telegram (по умолчанию) или max.
    'feedback' => [
        'primary' => env('FEEDBACK_PRIMARY_BOT', 'telegram'),
    ],

];
