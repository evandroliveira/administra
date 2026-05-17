<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Administrar') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-page">
        <div class="app-shell min-vh-100">
            @include('layouts.navigation')

            <div class="app-content flex-grow-1 min-vh-100">
                @isset($header)
                    <header class="app-header py-4 py-lg-5">
                        <div class="container-xxl">
                            <div class="app-header-surface rounded-4 shadow-sm p-4 p-lg-5">
                                {{ $header }}
                            </div>
                        </div>
                    </header>
                @endisset

                <main class="app-main pb-5">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
