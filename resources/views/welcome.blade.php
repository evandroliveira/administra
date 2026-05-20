<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Administrar') }}</title>

        @include('partials.vite-assets')
    </head>
    <body class="login-page">
        <div class="login-shell d-flex align-items-center justify-content-center min-vh-100 bg-gradient" style="background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);">
            <div class="container">
                <nav class="navbar rounded-4 border border-white border-opacity-25 bg-white bg-opacity-10 backdrop-blur px-3 px-lg-4 mb-4 mb-lg-5 shadow-lg">
                    <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center gap-2 fw-semibold text-white mb-0">
                        <span class="login-brand-icon d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-primary shadow-sm">
                            <i class="bi bi-shop"></i>
                        </span>
                        <span>Administrar</span>
                    </a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm rounded-pill px-3">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-light btn-sm rounded-pill px-3">Entrar</a>
                            <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">Criar empresa</a>
                        @endauth
                    </div>
                </nav>

                <div class="row g-0 overflow-hidden rounded-4 shadow-lg border border-white border-opacity-25 auth-surface bg-white bg-opacity-75">
                    <div class="col-lg-7 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(180deg, rgba(13,71,161,0.15) 0%, rgba(25,118,210,0.25) 100%), url('{{ asset('images/fundoInicio.png') }}'); background-size: cover;">
                        <section class="login-hero-panel p-5 text-primary-emphasis w-100">
                            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-4 shadow">Painel operacional</span>
                            <h1 class="display-4 fw-bold lh-sm mb-3">Gestão integrada para sua empresa</h1>
                            <p class="lead text-dark-emphasis mb-4">Centralize cadastros, vendas, cobranças e indicadores em um só lugar, com fluxo claro e menos atrito para sua equipe.</p>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-bag-check me-2 text-primary"></i> Operação comercial unificada</li>
                                <li class="mb-2"><i class="bi bi-graph-up-arrow me-2 text-primary"></i> Visão analítica diária</li>
                                <li class="mb-2"><i class="bi bi-cash-coin me-2 text-primary"></i> Controle financeiro simplificado</li>
                                <li class="mb-2"><i class="bi bi-bar-chart me-2 text-primary"></i> Relatórios e dashboards inteligentes</li>
                            </ul>
                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-4 shadow">Experimente grátis</a>
                        </section>
                    </div>
                    <!-- ...restante do conteúdo... -->
                                        <i class="bi bi-credit-card-2-front fs-3"></i>
                                        <div>
                                            <div class="fw-semibold">Assinatura e cobrança integradas</div>
                                            <div class="small text-white-50">A empresa retoma regularização sem sair do fluxo principal de trabalho.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-5">
                        <section class="login-card-panel bg-white p-4 p-lg-5 d-flex flex-column h-100">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary shadow-sm" style="width: 3.25rem; height: 3.25rem;">
                                    <i class="bi bi-grid-1x2-fill fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-uppercase text-body-secondary small fw-semibold mb-1">Boas-vindas</p>
                                    <h2 class="h3 mb-0 fw-semibold">Gestão comercial pronta para operar</h2>
                                </div>
                            </div>

                            <p class="text-body-secondary mb-4">Entre com sua conta para acompanhar indicadores, pedidos e operações da sua empresa em um único painel.</p>

                            <div class="rounded-4 bg-body-tertiary p-3 mb-4">
                                <div class="small text-body-secondary mb-1">Acesso inicial de demonstração</div>
                                <div class="fw-semibold">admin@system.local / admin123</div>
                            </div>

                            <div class="d-grid gap-3 mt-auto">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg rounded-pill">Abrir dashboard</a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg rounded-pill">Entrar na plataforma</a>
                                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg rounded-pill">Criar empresa e iniciar teste</a>
                                @endauth
                            </div>

                            <p class="small login-note mt-4 mb-0">Se a empresa estiver com pendência financeira, o sistema direciona o usuário para a retomada de pagamento na tela de assinatura.</p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>