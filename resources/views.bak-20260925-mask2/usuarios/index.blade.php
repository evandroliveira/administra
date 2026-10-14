<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Equipe</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Usuários da Empresa</h2>
                <p class="text-body-secondary mb-0">Gerencie acessos, perfis e o status operacional da equipe.</p>
            </div>

            @if ($resumo['limite_atingido'])
                <a href="{{ route('assinatura.show') }}" class="btn btn-warning btn-lg rounded-pill px-4">
                    Gerenciar plano
                </a>
            @else
                <a href="{{ route('usuarios.create') }}" class="btn btn-dark btn-lg rounded-pill px-4">
                    Novo usuário
                </a>
            @endif
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">
                    {{ session('status') }}
                </div>
            @endif

            <div class="alert alert-info rounded-4 mb-0">
                Usuários ativos: {{ $resumo['ativos'] }} / {{ $resumo['limite_usuarios'] }}.
                Restam {{ $resumo['usuarios_restantes'] }} vaga(s) no plano atual.
            </div>
            @if ($resumo['limite_atingido'])
                <div class="alert alert-warning rounded-4 mb-0">
                    O plano atual atingiu o limite de usuários ativos. Faça upgrade para cadastrar mais acessos.
                </div>
            @endif

            <section class="row g-3">
                <article class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 text-bg-dark">
                        <div class="card-body p-4">
                            <div class="small text-uppercase text-white-50 fw-semibold">Usuários totais</div>
                            <div class="display-6 fw-semibold mt-3">{{ $resumo['total'] }}</div>
                        </div>
                    </div>
                </article>
                <article class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="small text-uppercase text-body-secondary fw-semibold">Ativos</div>
                            <div class="display-6 fw-semibold text-dark mt-3">{{ $resumo['ativos'] }}</div>
                        </div>
                    </div>
                </article>
                <article class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="small text-uppercase text-body-secondary fw-semibold">Inativos</div>
                            <div class="display-6 fw-semibold text-dark mt-3">{{ $resumo['inativos'] }}</div>
                        </div>
                    </div>
                </article>
                <article class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="small text-uppercase text-body-secondary fw-semibold">Admins ativos</div>
                            <div class="display-6 fw-semibold text-dark mt-3">{{ $resumo['admins_ativos'] }}</div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                @if ($usuarios->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="px-4 py-3">Usuário</th>
                                    <th class="px-4 py-3">Contato</th>
                                    <th class="px-4 py-3">Perfil</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach ($usuarios as $usuarioVendas)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="fw-semibold text-dark">{{ $usuarioVendas->user?->name ?? '-' }}</div>
                                            <div class="text-body-secondary">{{ $usuarioVendas->user?->username ?? '-' }} • {{ $usuarioVendas->user?->email ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>{{ $usuarioVendas->telefone ?: '-' }}</div>
                                            <div class="text-body-secondary">{{ $usuarioVendas->cidade ?: '-' }}{{ $usuarioVendas->estado ? ' • '.$usuarioVendas->estado : '' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="badge text-bg-light border text-uppercase px-3 py-2">
                                                {{ $usuarioVendas->perfil?->nome ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($usuarioVendas->ativo)
                                                <span class="badge text-bg-success px-3 py-2 text-uppercase">Ativo</span>
                                            @else
                                                <span class="badge text-bg-secondary px-3 py-2 text-uppercase">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('usuarios.edit', $usuarioVendas) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-4 py-5 text-center text-body-secondary">
                        Nenhum usuário vinculado à empresa ainda.
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>