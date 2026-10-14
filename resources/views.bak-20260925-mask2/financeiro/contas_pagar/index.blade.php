<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Contas a Pagar</h2>
                <p class="text-body-secondary mb-0">Monitore vencimentos, pagamentos realizados e saldos em aberto com mais clareza.</p>
            </div>
            <a href="{{ route('contas.pagar.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">Nova conta a pagar</a>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">
                    {{ session('status') }}
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <form method="GET" action="{{ route('contas.pagar.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select form-select-lg">
                            <option value="">Todos</option>
                            @foreach (['aberta', 'parcial', 'quitada', 'vencida', 'cancelada'] as $status)
                                <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill">Filtrar</button>
                        </div>
                        <div class="col-sm-6 col-lg-2 d-grid">
                            <a href="{{ route('contas.pagar.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Fornecedor</th>
                            <th class="px-4 py-3">Vencimento</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Saldo</th>
                            <th class="px-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($contas as $conta)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">{{ $conta->id }}</td>
                                <td class="px-4 py-3">{{ $conta->fornecedor }}</td>
                                <td class="px-4 py-3">{{ optional($conta->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($conta->status) }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('contas.pagar.show', $conta) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-5 text-center text-body-secondary">Nenhuma conta encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $contas->links() }}</div>
        </div>
    </div>
</x-app-layout>
