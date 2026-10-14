<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Produtos</h2>
                <p class="text-body-secondary mb-0">Acompanhe estoque, preco e consumo do limite do plano atual.</p>
            </div>
            @if ($resumoPlano['limite_atingido'])
                <a href="{{ route('assinatura.show') }}" class="btn btn-warning btn-lg rounded-pill px-4">Gerenciar plano</a>
            @else
                <a href="{{ route('produtos.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">Novo produto</a>
            @endif
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">{{ session('status') }}</div>
            @endif
            <div class="alert alert-info rounded-4 mb-0">
                Produtos cadastrados: {{ $resumoPlano['produtos_cadastrados'] }} / {{ $resumoPlano['limite_produtos'] }}.
                Restam {{ $resumoPlano['produtos_restantes'] }} vaga(s) no plano atual.
            </div>
            @if ($resumoPlano['limite_atingido'])
                <div class="alert alert-warning rounded-4 mb-0">
                    O plano atual atingiu o limite de produtos. Faça upgrade para cadastrar mais itens.
                </div>
            @endif
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <form method="GET" action="{{ route('produtos.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-5">
                            <label for="q" class="form-label fw-semibold">Buscar produto</label>
                            <input id="q" type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nome ou código" class="form-control form-control-lg">
                        </div>
                        <div class="col-lg-3">
                            <label for="categoria_id" class="form-label fw-semibold">Categoria</label>
                            <select id="categoria_id" name="categoria_id" class="form-select form-select-lg">
                                <option value="">Todas as categorias</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected((string) ($filtros['categoria_id'] ?? '') === (string) $categoria->id)>{{ $categoria->nome }}{{ $categoria->ativo ? '' : ' (inativa)' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill">Buscar</button>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <a href="{{ route('produtos.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase"><tr><th class="px-4 py-3">Codigo</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Categoria</th><th class="px-4 py-3">Preco venda</th><th class="px-4 py-3">Estoque</th><th class="px-4 py-3 text-end">Acoes</th></tr></thead>
                        <tbody class="small">
                            @forelse ($produtos as $produto)
                                <tr>
                                    <td class="px-4 py-3 fw-semibold text-dark">{{ $produto->codigo }}</td>
                                    <td class="px-4 py-3">{{ $produto->nome }}</td>
                                    <td class="px-4 py-3">{{ $produto->categoria?->nome ?? 'Sem categoria' }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ $produto->estoque_atual }}</td>
                                    <td class="px-4 py-3 text-end"><div class="d-inline-flex gap-2"><a href="{{ route('produtos.show', $produto) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a><a href="{{ route('produtos.edit', $produto) }}" class="btn btn-sm btn-outline-warning rounded-pill">Editar</a></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-5 text-center text-body-secondary">Nenhum produto encontrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $produtos->links() }}</div>
        </div>
    </div>
</x-app-layout>
