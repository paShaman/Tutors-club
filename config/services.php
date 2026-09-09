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

];
