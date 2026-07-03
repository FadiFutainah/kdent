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
    'ultramsg' => [
        'instance' => env('ULTRAMSG_INSTANCE_ID'),
        'token'    => env('ULTRAMSG_TOKEN'),
    ],
    // أضيفي هذا الجزء هنا ليتعرف لارافيل على المتغيرات الجديدة
    'sptoday' => [
        'base_url' => env('SPTODAY_BASE_URL', 'https://api-v2.sp-today.com/api/v1'),
        'api_key'  => env('SPTODAY_API_KEY'),
        'timeout'  => env('SPTODAY_TIMEOUT', 10),
    ],
/*
    'lirascope' => [
        'base_url' => env('LIRASCOPE_BASE_URL', 'https://lirascope.syria-cloud.sy'),
        'timeout' => env('LIRASCOPE_TIMEOUT', 10),
    ],*/

];
