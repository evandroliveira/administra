@php
    $user = auth()->user();
    $empresa = $user?->usuarioVendas?->empresa;
    $assinaturaAtualMenu = $empresa?->assinaturaAtual()?->with('plano')->first();
    $permitePromissoriaMenu = $assinaturaAtualMenu?->plano?->permite_promissoria ?? true;
    $isAdmin = $user?->hasRole('admin') ?? false;
    $isGerente = $user?->hasRole('gerente') ?? false;
    $isVendedor = $user?->hasRole('vendedor') ?? false;
    $isRecepcao = $user?->hasRole('recepcao') ?? false;
    $empresaNome = $empresa?->nome;
    $perfilNome = $user?->usuarioVendas?->perfil?->nome ?? 'Conta';
    $menuItems = [
        [
            'label' => 'Dashboard',
            'route' => route('dashboard'),
            'active' => 'dashboard',
            'icon' => 'bi-speedometer2',
        ],
    ];

    if ($isAdmin || $isGerente || $isVendedor || $isRecepcao) {
        $menuItems[] = [
            'label' => 'Clientes',
            'route' => route('clientes.index'),
            'active' => 'clientes.*',
            'icon' => 'bi-people',
        ];
        $menuItems[] = [
            'label' => 'Assinatura',
            'route' => route('assinatura.show'),
            'active' => 'assinatura.*',
            'icon' => 'bi-credit-card',
        ];
    }

    if ($isAdmin || $isGerente) {
        $menuItems[] = [
            'label' => 'Produtos',
            'route' => route('produtos.index'),
            'active' => 'produtos.*',
            'icon' => 'bi-box-seam',
        ];
        $menuItems[] = [
            'label' => 'Categorias',
            'route' => route('categorias.index'),
            'active' => 'categorias.*',
            'icon' => 'bi-tags',
        ];
        $menuItems[] = [
            'label' => 'Relatórios',
            'route' => route('relatorios.index'),
            'active' => 'relatorios.*',
            'icon' => 'bi-bar-chart',
        ];
        $menuItems[] = [
            'label' => 'Contas a Receber',
            'route' => route('contas.receber.index'),
            'active' => 'contas.receber.*',
            'icon' => 'bi-cash-coin',
        ];
        $menuItems[] = [
            'label' => 'Contas a Pagar',
            'route' => route('contas.pagar.index'),
            'active' => 'contas.pagar.*',
            'icon' => 'bi-wallet2',
        ];
    }

    if ($isAdmin) {
        $menuItems[] = [
            'label' => 'Usuários',
            'route' => route('usuarios.index'),
            'active' => 'usuarios.*',
            'icon' => 'bi-person-gear',
        ];
        $menuItems[] = [
            'label' => 'Empresa',
            'route' => route('empresa.edit'),
            'active' => 'empresa.*',
            'icon' => 'bi-building',
        ];
    }

    if ($isAdmin || $isGerente || $isVendedor) {
        $menuItems[] = [
            'label' => 'Vendas',
            'route' => route('vendas.index'),
            'active' => 'vendas.*',
            'icon' => 'bi-bag-check',
        ];
    }

    if (($isAdmin || $isGerente) && $permitePromissoriaMenu) {
        $menuItems[] = [
            'label' => 'Promissórias',
            'route' => route('promissorias.index'),
            'active' => 'promissorias.*',
            'icon' => 'bi-journal-text',
        ];
    }
@endphp

<div class="d-lg-none">
    <nav class="navbar app-mobilebar sticky-top border-bottom border-white border-opacity-50 shadow-sm">
        <div class="container-fluid px-3 py-3">
            <a href="{{ route('dashboard') }}" class="navbar-brand app-sidebar-brand d-flex align-items-center gap-3 mb-0 text-decoration-none">
                <span class="login-brand-icon app-brand-mark d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow-sm">
                    <i class="bi bi-grid-1x2-fill"></i>
                </span>
                <span>
                    <span class="d-block fw-semibold text-dark">{{ config('app.name', 'Administrar') }}</span>
                    <span class="d-block small text-body-secondary app-navbar-meta">{{ $empresaNome ?: 'Painel operacional' }}</span>
                </span>
            </a>

            <button class="btn btn-light border rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarMobile" aria-controls="appSidebarMobile" aria-label="Abrir menu">
                <i class="bi bi-list fs-5"></i>
                <span>Menu</span>
            </button>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start app-sidebar-offcanvas border-0" tabindex="-1" id="appSidebarMobile" aria-labelledby="appSidebarMobileLabel">
        <div class="offcanvas-header px-4 pt-4 pb-0">
            <div>
                <h2 id="appSidebarMobileLabel" class="visually-hidden">Menu principal</h2>
                <a href="{{ route('dashboard') }}" class="app-sidebar-brand d-flex align-items-center gap-3 text-decoration-none">
                    <span class="login-brand-icon app-brand-mark d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow-sm">
                        <i class="bi bi-grid-1x2-fill"></i>
                    </span>
                    <span>
                        <span class="d-block fw-semibold text-dark">{{ config('app.name', 'Administrar') }}</span>
                        <span class="d-block small text-body-secondary app-navbar-meta">{{ $empresaNome ?: 'Painel operacional' }}</span>
                    </span>
                </a>
            </div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>

        <div class="offcanvas-body p-4 d-flex flex-column">
            <div class="app-sidebar-company">
                <div class="app-sidebar-section-label">Empresa</div>
                <div class="fw-semibold text-dark">{{ $empresaNome ?: 'Painel operacional' }}</div>
            </div>

            <ul class="nav flex-column gap-2 app-sidebar-nav mt-4">
                @foreach ($menuItems as $item)
                    <li class="nav-item">
                        <a href="{{ $item['route'] }}" class="nav-link app-sidebar-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" data-bs-dismiss="offcanvas">
                            <i class="bi {{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="app-sidebar-footer mt-auto">
                <div class="app-user-card">
                    <div class="fw-semibold text-dark">{{ $user?->name }}</div>
                    <div class="small text-body-secondary">{{ ucfirst((string) $perfilNome) }}{{ $user?->email ? ' • '.$user->email : '' }}</div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <a class="btn btn-light border rounded-pill d-inline-flex align-items-center justify-content-center gap-2" href="{{ route('profile.edit') }}" data-bs-dismiss="offcanvas">
                        <i class="bi bi-sliders2 text-primary"></i>
                        <span>Perfil</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger rounded-pill w-100 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<aside class="app-sidebar d-none d-lg-flex">
    <div class="app-sidebar-inner w-100">
        <a href="{{ route('dashboard') }}" class="app-sidebar-brand d-flex align-items-center gap-3 text-decoration-none">
            <span class="login-brand-icon app-brand-mark d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow-sm">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>
            <span>
                <span class="d-block fw-semibold text-dark">{{ config('app.name', 'Administrar') }}</span>
                <span class="d-block small text-body-secondary app-navbar-meta">{{ $empresaNome ?: 'Painel operacional' }}</span>
            </span>
        </a>

        <div class="app-sidebar-company mt-4">
            <div class="app-sidebar-section-label">Empresa</div>
            <div class="fw-semibold text-dark">{{ $empresaNome ?: 'Painel operacional' }}</div>
        </div>

        <ul class="nav flex-column gap-2 app-sidebar-nav mt-4">
            @foreach ($menuItems as $item)
                <li class="nav-item">
                    <a href="{{ $item['route'] }}" class="nav-link app-sidebar-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="app-sidebar-footer mt-auto">
            <div class="app-user-card">
                <div class="fw-semibold text-dark">{{ $user?->name }}</div>
                <div class="small text-body-secondary">{{ ucfirst((string) $perfilNome) }}{{ $user?->email ? ' • '.$user->email : '' }}</div>
            </div>

            <div class="d-grid gap-2 mt-3">
                <a class="btn btn-light border rounded-pill d-inline-flex align-items-center justify-content-center gap-2" href="{{ route('profile.edit') }}">
                    <i class="bi bi-sliders2 text-primary"></i>
                    <span>Perfil</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-pill w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
