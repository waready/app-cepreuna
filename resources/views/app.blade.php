<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CepreUNA') }}</title>
    @laravelPWA
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/theme.css') }}">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <style>
        @media screen and (max-width: 576px) {
            html {
                font-size: 14px !important;
            }
        }

        html {
            font-size: 13px;
        }

    </style>

    <!-- Scripts -->
    @routes
    <script src="{{ mix('js/app.js') }}" defer></script>
</head>

<body class="font-sans antialiased">
    @inertia

    {{-- @env('local')
            <script src="http://localhost:3000/browser-sync/browser-sync-client.js"></script>
        @endenv --}}
</body>

</html>
