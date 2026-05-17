<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Administrar') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-page">
        <div class="container py-4 py-lg-5">
            <nav class="navbar rounded-4 border border-white border-opacity-25 bg-white bg-opacity-10 backdrop-blur px-3 px-lg-4 mb-4 mb-lg-5 shadow-sm shell-glass-nav">
                <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center gap-2 fw-semibold text-white mb-0">
                    <span class="login-brand-icon d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-primary shadow-sm">
                        <i class="bi bi-shop"></i>
                    </span>
                    <span>Administrar</span>
                </a>

                <div class="ms-auto d-flex align-items-center gap-2">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-light btn-sm rounded-pill px-3">Entrar</a>
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">Criar empresa</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm rounded-pill px-3">Dashboard</a>
                    @endguest
                </div>
            </nav>

            {{ $slot }}
        </div>
    </body>
</html>
