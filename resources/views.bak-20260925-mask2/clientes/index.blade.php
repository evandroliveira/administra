<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Cadastros</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Clientes</h2>
                <p class="text-body-secondary mb-0">Gerencie a base de clientes e acompanhe documentos, contato e credito.</p>
            </div>
            <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">Novo cliente</a>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">{{ session('status') }}</div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <form method="GET" action="{{ route('clientes.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label for="q" class="form-label fw-semibold">Buscar cliente</label>
                            <input id="q" type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nome, documento ou e-mail" class="form-control form-control-lg">
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill">Buscar</button>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <a href="{{ route('clientes.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Limite</th>
                            <th class="px-4 py-3 text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">{{ $cliente->nome }}</td>
                                <td class="px-4 py-3">{{ $cliente->cpf_cnpj }}</td>
                                <td class="px-4 py-3">{{ $cliente->telefone }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $cliente->limite_credito, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a>
                                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-outline-warning rounded-pill">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-5 text-center text-body-secondary">Nenhum cliente encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $clientes->links() }}</div>
        </div>
    </div>
</x-app-layout>
