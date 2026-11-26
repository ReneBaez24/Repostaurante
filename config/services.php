<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'openfoodfacts' => [
        // Para pruebas sin cuota usa https://world.openfoodfacts.net
        'base_url' => env('OPENFOODFACTS_URL', 'https://world.openfoodfacts.org'),
        // Open Food Facts pide un User-Agent con el nombre de la app y un correo de contacto
        'user_agent' => env('OPENFOODFACTS_USER_AGENT', 'PunkBrothers/1.0 (contacto@punkbrothers.com)'),
        'timeout' => (int) env('OPENFOODFACTS_TIMEOUT', 8),
        'cache_minutos' => (int) env('OPENFOODFACTS_CACHE_MINUTOS', 1440),
        // Peticiones simultáneas al importar por lotes (la API permite ~100 consultas de producto por minuto)
        'concurrencia' => (int) env('OPENFOODFACTS_CONCURRENCIA', 10),
    ],

];
