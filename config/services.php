<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tmdb' => [
        'key'      => env('TMDB_API_KEY'),
        'language' => env('TMDB_LANGUAGE', 'pt-BR'),
    ],

    'jackett' => [
        'url' => env('JACKETT_URL', 'http://jackett:9117'),
        'key' => env('JACKETT_KEY'),
    ],

    'subdl' => [
        'key' => env('SUBDL_API_KEY'),
    ],

    'opensubtitles' => [
        'key'      => env('OPENSUBTITLES_API_KEY'),
        'username' => env('OPENSUBTITLES_USERNAME'),
        'password' => env('OPENSUBTITLES_PASSWORD'),
    ],

    'vapid' => [
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'cinemacity' => [
        'email'            => env('CINEMACITY_EMAIL'),
        'password'         => env('CINEMACITY_PASSWORD'),
        'cookie_user_id'   => env('CINEMACITY_COOKIE_USER_ID'),
        'cookie_password'  => env('CINEMACITY_COOKIE_PASSWORD'),
        'flaresolverr'     => env('FLARESOLVERR_URL', 'http://flaresolverr:8191'),
    ],

];
