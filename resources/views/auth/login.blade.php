<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Entrar | {{ config('app.name', 'Administrar') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-page">
        <div class="login-shell d-flex align-items-center py-4 py-lg-5">
            <div class="container">
                <nav class="navbar rounded-4 border border-white border-opacity-25 bg-white bg-opacity-10 backdrop-blur px-3 px-lg-4 mb-4 mb-lg-5 shadow-sm">
                    <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center gap-2 fw-semibold text-white mb-0">
                        <span class="login-brand-icon d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-primary shadow-sm">
                            <i class="bi bi-shop"></i>
                        </span>
                        <span>Administrar</span>
                    </a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">Criar empresa</a>
                    </div>
                </nav>

                <div class="row g-0 overflow-hidden rounded-4 shadow-lg border border-white border-opacity-25 auth-surface">
                    <div class="col-lg-6 d-none d-lg-flex">
                        <section class="login-hero-panel p-5 text-white w-100" style="background-image: linear-gradient(180deg, rgba(7, 16, 30, 0.24) 0%, rgba(7, 16, 30, 0.72) 100%), url('{{ asset('images/lojagestao.png') }}');">
                            <span class="badge rounded-pill text-bg-light px-3 py-2 text-primary mb-4">Painel operacional</span>
                            <h1 class="display-6 fw-semibold lh-sm">Vendas, estoque e financeiro no mesmo fluxo.</h1>
                            <p class="lead text-white-50 mt-4 mb-0">Acompanhe indicadores, pedidos e a saude financeira da empresa em uma tela de acesso mais clara e objetiva.</p>

                            <div class="row row-cols-1 g-3 mt-4">
                                <div class="col">
                                    <div class="login-stat d-flex align-items-start gap-3">
                                        <i class="bi bi-graph-up-arrow fs-3"></i>
                                        <div>
                                            <div class="fw-semibold">Indicadores em tempo real</div>
                                            <div class="small text-white-50">Faturamento, recebimentos e performance reunidos no mesmo painel.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="login-stat d-flex align-items-start gap-3">
                                        <i class="bi bi-shield-check fs-3"></i>
                                        <div>
                                            <div class="fw-semibold">Acesso validado pela empresa</div>
                                            <div class="small text-white-50">Usuarios ativos entram e o sistema redireciona pendencias automaticamente.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="login-stat d-flex align-items-start gap-3">
                                        <i class="bi bi-credit-card-2-front fs-3"></i>
                                        <div>
                                            <div class="fw-semibold">Billing integrado</div>
                                            <div class="small text-white-50">Se houver cobranca pendente, voce pode retomar o pagamento na tela de assinatura.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="login-card-panel bg-white p-4 p-lg-5">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary shadow-sm" style="width: 3.25rem; height: 3.25rem;">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-uppercase text-body-secondary small fw-semibold mb-1">Acesso seguro</p>
                                    <h2 class="h3 mb-0 fw-semibold">Entrar na plataforma</h2>
                                </div>
                            </div>

                            <p class="text-body-secondary mb-4">Use o email ou o usuario da conta para abrir o painel administrativo com rapidez.</p>

                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                                </div>
                            @endif

                            @if (session('warning'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('warning') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email ou usuario</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-body-tertiary border-end-0">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input
                                            type="text"
                                            class="form-control border-start-0"
                                            id="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            required
                                            autofocus
                                            autocomplete="username"
                                            placeholder="voce@empresa.com ou seu_usuario"
                                        >
                                    </div>
                                    @error('email')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">Senha</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-body-tertiary border-end-0">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input
                                            type="password"
                                            class="form-control border-start-0"
                                            id="password"
                                            name="password"
                                            required
                                            autocomplete="current-password"
                                            placeholder="Digite sua senha"
                                        >
                                    </div>
                                    @error('password')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember">Lembrar de mim</label>
                                    </div>

                                    <a href="{{ route('password.request') }}" class="link-primary text-decoration-none fw-semibold">Esqueci minha senha</a>
                                </div>

                                <div class="d-grid gap-3">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">Entrar</button>
                                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg rounded-pill">Criar empresa e iniciar teste</a>
                                </div>
                            </form>

                            <div class="rounded-4 bg-body-tertiary p-3 mt-4">
                                <div class="small text-body-secondary mb-1">Acesso inicial de demonstracao</div>
                                <div class="fw-semibold">admin@system.local / admin123</div>
                            </div>

                            <p class="small login-note mt-4 mb-0">Se a empresa estiver com pendencia financeira, o sistema vai redirecionar voce para retomar o pagamento na tela de assinatura apos o login.</p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>