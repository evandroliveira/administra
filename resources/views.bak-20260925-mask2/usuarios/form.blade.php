<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Equipe</span>
            <h2 class="h1 fw-semibold text-dark mb-2">
                {{ $modo === 'create' ? 'Novo usuário' : 'Editar usuário' }}
            </h2>
            <p class="text-body-secondary mb-0">Defina perfil, acesso e dados de contato do colaborador.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="row g-4">
            <div class="col-12">
                <div class="alert alert-info rounded-4 mb-0">
                Usuários ativos: {{ $resumo['ativos'] }} / {{ $resumo['limite_usuarios'] }}.
                Restam {{ $resumo['usuarios_restantes'] }} vaga(s) no plano atual.
                </div>
            </div>

            <section class="col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                @if ($errors->any())
                    <div class="alert alert-danger rounded-4 mb-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($modo === 'create' && $resumo['limite_atingido'])
                    <div class="alert alert-warning rounded-4 mb-0">
                        O plano atual permite até {{ $resumo['limite_usuarios'] }} usuário(s) ativo(s). Faça upgrade para cadastrar mais acessos.

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('assinatura.show') }}" class="btn btn-warning rounded-pill px-4">
                                Gerenciar plano
                            </a>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                Voltar para usuários
                            </a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ $modo === 'create' ? route('usuarios.store') : route('usuarios.update', $usuarioEdicao) }}" class="row g-3">
                        @csrf
                        @if ($modo === 'edit')
                            @method('PATCH')
                        @endif

                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Usuário</label>
                            <input id="username" name="username" type="text" value="{{ $valores['username'] }}" required class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Nome completo</label>
                            <input id="name" name="name" type="text" value="{{ $valores['name'] }}" required class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input id="email" name="email" type="email" value="{{ $valores['email'] }}" required class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <label for="perfil" class="form-label fw-semibold">Perfil</label>
                            <select id="perfil" name="perfil" required class="form-select form-select-lg">
                                @foreach ($perfis as $perfil)
                                    <option value="{{ $perfil->nome }}" @selected($valores['perfil'] === $perfil->nome)>{{ ucfirst($perfil->nome) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label fw-semibold">Telefone</label>
                            <input id="telefone" name="telefone" type="text" value="{{ $valores['telefone'] }}" class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check form-switch border rounded-4 px-4 py-3 w-100">
                                <input type="hidden" name="ativo" value="0">
                                <input type="checkbox" name="ativo" value="1" class="form-check-input" role="switch" @checked($valores['ativo'])>
                                <span class="form-check-label ms-2 fw-semibold text-dark">Usuário ativo</span>
                            </label>
                        </div>

                        <div class="col-12">
                            <label for="endereco" class="form-label fw-semibold">Endereço</label>
                            <textarea id="endereco" name="endereco" rows="3" class="form-control form-control-lg">{{ $valores['endereco'] }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="cidade" class="form-label fw-semibold">Cidade</label>
                            <input id="cidade" name="cidade" type="text" value="{{ $valores['cidade'] }}" class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <div class="row g-3">
                            <div class="col-4">
                                <label for="estado" class="form-label fw-semibold">UF</label>
                                <input id="estado" name="estado" type="text" maxlength="2" value="{{ $valores['estado'] }}" class="form-control form-control-lg">
                            </div>

                            <div class="col-8">
                                <label for="cep" class="form-label fw-semibold">CEP</label>
                                <input id="cep" name="cep" type="text" value="{{ $valores['cep'] }}" class="form-control form-control-lg">
                            </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">{{ $modo === 'create' ? 'Senha' : 'Nova senha' }}</label>
                            <input id="password" name="password" type="password" {{ $modo === 'create' ? 'required' : '' }} class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmar senha</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" {{ $modo === 'create' ? 'required' : '' }} class="form-control form-control-lg">
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2 pt-3 border-top mt-3">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill px-4">
                                {{ $modo === 'create' ? 'Cadastrar usuário' : 'Salvar alterações' }}
                            </button>

                            <a href="{{ route('usuarios.index') }}" class="btn btn-light btn-lg rounded-pill px-4">
                                Voltar
                            </a>
                        </div>
                    </form>
                @endif
                    </div>
                </div>
            </section>

            <aside class="col-xl-4 d-flex flex-column gap-4">
                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Resumo da equipe</h3>
                    <div class="mt-4 d-flex flex-column gap-3 small text-body-secondary">
                        <div>
                            <div class="text-uppercase fw-semibold small">Usuários ativos</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumo['ativos'] }} / {{ $resumo['limite_usuarios'] }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold small">Vagas restantes</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumo['usuarios_restantes'] }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold small">Admins ativos</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumo['admins_ativos'] }}</div>
                        </div>
                        <div>
                            <div class="text-uppercase fw-semibold small">Inativos</div>
                            <div class="mt-1 fs-3 fw-semibold text-dark">{{ $resumo['inativos'] }}</div>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                    <h3 class="h5 fw-semibold text-dark">Regra crítica</h3>
                    <p class="text-body-secondary mt-3 mb-0">
                        A empresa precisa manter pelo menos um administrador ativo. Ao trocar o perfil ou desativar um acesso, essa proteção é validada automaticamente.
                    </p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>