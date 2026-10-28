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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'banco_preguntas' => [
        'url' => env('BANCO_PREGUNTAS_API_URL'),
        'token' => env('BANCO_PREGUNTAS_API_TOKEN'),
        'timeout' => (int) env('BANCO_PREGUNTAS_API_TIMEOUT', 30),
    ],

    'pago_archivos' => [
        'url' => env('PAGO_ARCHIVOS_API_URL') ?: env('BANCO_PREGUNTAS_API_URL'),
        'token' => env('PAGO_ARCHIVOS_API_TOKEN') ?: env('BANCO_PREGUNTAS_API_TOKEN'),
        'timeout' => (int) env('PAGO_ARCHIVOS_API_TIMEOUT', 30),
    ],

    // Tramite documentario: vive en el backend de sistemas, que es quien
    // genera los PDF. Comparte la misma credencial de integracion.
    'tramite' => [
        // Sin TRAMITE_API_URL se deriva de la del banco de preguntas: las dos
        // cuelgan de integraciones/cepre-app/ en el mismo backend. Usarla tal
        // cual mandaba los pedidos a .../banco-preguntas/expediente y daba 404.
        'url' => env('TRAMITE_API_URL')
            ?: preg_replace('#/banco-preguntas/*$#', '/tramite', (string) env('BANCO_PREGUNTAS_API_URL')),
        'token' => env('TRAMITE_API_TOKEN') ?: env('BANCO_PREGUNTAS_API_TOKEN'),
        // Generar el expediente completo tarda mas que una consulta normal.
        'timeout' => (int) env('TRAMITE_API_TIMEOUT', 60),
    ],

];
