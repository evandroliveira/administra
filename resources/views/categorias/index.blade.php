<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Categorias</h2>
                <p class="text-body-secondary mb-0">Organize os produtos por grupos para acelerar cadastros, busca e operacao diaria.</p>
            </div>
            <a href="{{ route('categorias.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">Nova categoria</a>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">{{ session('status') }}</div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <form method="GET" action="{{ route('categorias.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label for="q" class="form-label fw-semibold">Buscar categoria</label>
                            <input id="q" type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nome ou descricao" class="form-control form-control-lg">
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill">Buscar</button>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <a href="{{ route('categorias.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
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
                                <th class="px-4 py-3">Descricao</th>
                                <th class="px-4 py-3">Produtos</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($categorias as $categoria)
                                @php
                                    $acaoCategoria = $categoria->produtos_count > 0 ? 'Inativar' : 'Excluir';
                                    $confirmacao = $categoria->produtos_count > 0
                                        ? 'A categoria possui produtos vinculados e sera inativada. Deseja continuar?'
                                        : 'Deseja remover esta categoria?';
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 fw-semibold text-dark">{{ $categoria->nome }}</td>
                                    <td class="px-4 py-3 text-body-secondary">{{ $categoria->descricao ?: 'Sem descricao' }}</td>
                                    <td class="px-4 py-3">{{ $categoria->produtos_count }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge rounded-pill {{ $categoria->ativo ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $categoria->ativo ? 'Ativa' : 'Inativa' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-sm btn-outline-warning rounded-pill">Editar</a>
                                            <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" onsubmit="return confirm('{{ $confirmacao }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ $acaoCategoria }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-5 text-center text-body-secondary">Nenhuma categoria encontrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $categorias->links() }}</div>
        </div>
    </div>
</x-app-layout>