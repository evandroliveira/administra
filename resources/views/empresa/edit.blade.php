<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Configuração</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Dados da Empresa</h2>
                <p class="text-body-secondary mb-0">Atualize as informações usadas no onboarding, cobrança e relatórios.</p>
            </div>

            <a href="{{ route('assinatura.show') }}" class="btn btn-light btn-lg rounded-pill px-4">
                Voltar para assinatura
            </a>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="row g-4">
            <div class="col-12">
                <div class="alert alert-info rounded-4 mb-0">
                Usuários ativos: {{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}.
                Produtos cadastrados: {{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}.
                </div>
            </div>

            <section class="col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                @if (session('status'))
                    <div class="alert alert-success rounded-4 mb-4">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4 mb-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-6">
                    <h3 class="h4 fw-semibold text-dark mb-2">Cadastro da Empresa</h3>
                    <p class="text-body-secondary mb-0">Esses dados alimentam a cobrança recorrente, a identificação da empresa e os relatórios exportados.</p>
                </div>

                <form method="POST" action="{{ route('empresa.update') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    @method('PATCH')

                    <div class="col-12">
                        <label for="nome" class="form-label fw-semibold">Nome</label>
                        <input id="nome" name="nome" type="text" value="{{ old('nome', $empresa->nome) }}" required class="form-control form-control-lg">
                    </div>

                    <div class="col-md-6">
                        <label for="documento" class="form-label fw-semibold">Documento</label>
                        <input id="documento" name="documento" type="text" value="{{ old('documento', $empresa->documento) }}" class="form-control form-control-lg">
                    </div>

                    <div class="col-md-6">
                        <label for="telefone" class="form-label fw-semibold">Telefone</label>
                        <input id="telefone" name="telefone" type="text" value="{{ old('telefone', $empresa->telefone) }}" class="form-control form-control-lg">
                    </div>

                    <div class="col-12">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $empresa->email) }}" class="form-control form-control-lg">
                    </div>

                    <div class="col-12">
                        <div class="border rounded-4 p-4">
                        <label for="logo" class="form-label fw-semibold">Logo da empresa</label>

                        @if ($logoUrl)
                            <div class="d-flex flex-column gap-3 mt-3">
                                <img src="{{ $logoUrl }}" alt="Logo atual" class="rounded-4 border bg-white p-2 object-fit-contain" style="height: 84px; max-width: 220px; width: auto;">

                                <label class="form-check d-flex align-items-center gap-2 text-danger-emphasis mb-0">
                                    <input type="hidden" name="remover_logo" value="0">
                                    <input type="checkbox" name="remover_logo" value="1" class="form-check-input" @checked(old('remover_logo') === '1')>
                                    <span class="form-check-label fw-semibold">Remover logo atual</span>
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="remover_logo" value="0">
                        @endif

                        <input id="logo" name="logo" type="file" accept="image/*" class="form-control form-control-lg mt-3">
                        <p class="small text-body-secondary mt-2 mb-0">Formatos aceitos: JPG, PNG, GIF ou WebP. Tamanho máximo de 2 MB.</p>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2 pt-3 border-top mt-3">
                        <button type="submit" class="btn btn-dark btn-lg rounded-pill px-4">
                            Salvar alterações
                        </button>

                        <a href="{{ route('assinatura.show') }}" class="btn btn-light btn-lg rounded-pill px-4">
                            Voltar
                        </a>
                    </div>
                </form>
                    </div>
                </div>
            </section>

            <aside class="col-xl-4 d-flex flex-column gap-4">
                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Situação Atual</h3>

                    <dl class="mt-4 d-flex flex-column gap-3 small text-body-secondary mb-0">
                        <div>
                            <dt class="text-uppercase fw-semibold small">Slug</dt>
                            <dd class="mt-1 fw-semibold text-dark">{{ $empresa->slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-uppercase fw-semibold small">Status da empresa</dt>
                            <dd class="mt-1 fw-semibold text-dark">{{ $empresa->ativa ? 'Ativa' : 'Inativa' }}</dd>
                        </div>
                        <div>
                            <dt class="text-uppercase fw-semibold small">Plano</dt>
                            <dd class="mt-1 fw-semibold text-dark">{{ $assinatura->plano->nome }}</dd>
                        </div>
                        <div>
                            <dt class="text-uppercase fw-semibold small">Status da assinatura</dt>
                            <dd class="mt-1 fw-semibold text-dark">{{ $assinatura->status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-uppercase fw-semibold small">Gateway</dt>
                            <dd class="mt-1 fw-semibold text-dark">{{ $assinatura->gateway ?: 'Local' }}</dd>
                        </div>
                    </dl>
                    </div>
                </section>

                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Consumo Atual</h3>

                    <div class="mt-4 d-flex flex-column gap-3 small text-body-secondary">
                        <div>
                            <div class="text-uppercase fw-semibold small">Usuários</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold small">Produtos</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}</div>
                        </div>
                    </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>