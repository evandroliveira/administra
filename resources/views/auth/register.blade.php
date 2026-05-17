<x-guest-layout>
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">
            <div class="rounded-4 border border-white border-opacity-25 bg-white shadow-lg p-4 p-lg-5">
                <div class="row g-4 align-items-start mb-4">
                    <div class="col-lg-5">
                        <span class="badge rounded-pill text-bg-primary px-3 py-2">Onboarding SaaS</span>
                        <h1 class="display-6 fw-semibold mt-3 mb-3">Criar empresa e iniciar operação</h1>
                        <p class="text-body-secondary mb-0">Cadastre a empresa, o primeiro administrador e já entre na assinatura inicial do sistema.</p>
                    </div>
                    <div class="col-lg-7">
                        <div class="alert alert-info border-0 rounded-4 mb-0">
                            <div class="fw-semibold">{{ $planoPadrao->nome }}</div>
                            <div class="mt-1">R$ {{ number_format((float) $planoPadrao->valor_mensal, 2, ',', '.') }} / mês</div>
                            <div class="mt-1">Limite de {{ $planoPadrao->limite_usuarios }} usuários e {{ $planoPadrao->limite_produtos }} produtos.</div>
                            @if ($cobrancaConfigurada)
                                <div class="mt-2">A cobrança recorrente será sincronizada no {{ $providerLabel }} logo após o cadastro.</div>
                            @else
                                <div class="mt-2">O ambiente está em modo local: a assinatura será criada sem abrir checkout automático.</div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4">
                        <div class="fw-semibold mb-2">Revise os campos abaixo.</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <section class="card border-0 shadow-sm h-100 rounded-4">
                                <div class="card-body p-4">
                                    <h2 class="h4 fw-semibold mb-1">Empresa</h2>
                                    <p class="text-body-secondary mb-4">Esses dados serão usados no billing, nos relatórios e na identificação da conta.</p>

                                    <div class="mb-3">
                                        <label for="nome_empresa" class="form-label fw-semibold">Nome da empresa</label>
                                        <input id="nome_empresa" class="form-control form-control-lg" type="text" name="nome_empresa" value="{{ old('nome_empresa') }}" required autofocus autocomplete="organization">
                                        @error('nome_empresa')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="documento" class="form-label fw-semibold">CPF ou CNPJ</label>
                                        <input id="documento" class="form-control form-control-lg" type="text" name="documento" value="{{ old('documento') }}" autocomplete="off">
                                        @error('documento')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="email_empresa" class="form-label fw-semibold">E-mail da empresa</label>
                                        <input id="email_empresa" class="form-control form-control-lg" type="email" name="email_empresa" value="{{ old('email_empresa') }}" autocomplete="email">
                                        @error('email_empresa')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefone_empresa" class="form-label fw-semibold">Telefone</label>
                                        <input id="telefone_empresa" class="form-control form-control-lg" type="text" name="telefone_empresa" value="{{ old('telefone_empresa') }}" autocomplete="tel">
                                        @error('telefone_empresa')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="logo" class="form-label fw-semibold">Logo da empresa</label>
                                        <input id="logo" name="logo" type="file" accept="image/*" class="form-control form-control-lg">
                                        @error('logo')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Opcional. Aceita JPG, PNG, GIF e WebP com até 2 MB.</div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-6">
                            <section class="card border-0 shadow-sm h-100 rounded-4">
                                <div class="card-body p-4">
                                    <h2 class="h4 fw-semibold mb-1">Primeiro administrador</h2>
                                    <p class="text-body-secondary mb-4">Esse usuário já entra com acesso administrativo completo à empresa criada.</p>

                                    <div class="mb-3">
                                        <label for="name" class="form-label fw-semibold">Nome completo</label>
                                        <input id="name" class="form-control form-control-lg" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
                                        @error('name')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="username" class="form-label fw-semibold">Usuário interno</label>
                                        <input id="username" class="form-control form-control-lg" type="text" name="username" value="{{ old('username') }}" required autocomplete="username">
                                        @error('username')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">E-mail de acesso</label>
                                        <input id="email" class="form-control form-control-lg" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                                        @error('email')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Senha</label>
                                        <input id="password" class="form-control form-control-lg" type="password" name="password" required autocomplete="new-password">
                                        @error('password')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="form-label fw-semibold">Confirmar senha</label>
                                        <input id="password_confirmation" class="form-control form-control-lg" type="password" name="password_confirmation" required autocomplete="new-password">
                                        @error('password_confirmation')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-top pt-4 mt-4">
                        <a class="text-decoration-none fw-semibold" href="{{ route('login') }}">Voltar ao login</a>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Criar empresa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
