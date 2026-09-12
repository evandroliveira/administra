<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0d6efd">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <title>{{ config('app.name', 'Administrar') }}</title>

        @include('partials.vite-assets')
        @include('partials.money-mask-assets')
    </head>
    <body class="app-page">
        <div class="app-shell min-vh-100 bg-body-tertiary">
            @include('layouts.navigation')

            <button type="button" class="btn btn-primary rounded-pill shadow position-fixed bottom-0 end-0 m-3 d-none" data-install-app>
                <i class="bi bi-download me-1"></i>
                Instalar aplicativo
            </button>

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
