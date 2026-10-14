<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Conta a Pagar #{{ $conta->id }}</h2>
            <p class="text-body-secondary mb-0">Detalhe da despesa, saldos e pagamentos vinculados.</p>
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
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-3 small">
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Fornecedor</div><div class="text-dark">{{ $conta->fornecedor }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Status</div><div class="text-dark">{{ ucfirst($conta->status) }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Vencimento</div><div class="text-dark">{{ optional($conta->data_vencimento)->format('d/m/Y') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Valor original</div><div class="text-dark">R$ {{ number_format((float) $conta->valor_original, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Valor pago</div><div class="text-dark">R$ {{ number_format((float) $conta->valor_pago, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Saldo</div><div class="text-dark">R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <h3 class="h4 fw-semibold text-dark mb-4">Registrar Pagamento</h3>
                <form method="POST" action="{{ route('contas.pagar.pagamentos.store', $conta) }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="valor" class="form-label fw-semibold">Valor</label>
                        <input id="valor" type="number" step="0.01" min="0.01" name="valor" required class="form-control form-control-lg" value="{{ old('valor') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="metodo" class="form-label fw-semibold">Método</label>
                        <select id="metodo" name="metodo" class="form-select form-select-lg">
                            @foreach (['dinheiro', 'cheque', 'cartao', 'pix', 'transferencia', 'outro'] as $metodo)
                                <option value="{{ $metodo }}" @selected(old('metodo', 'transferencia') === $metodo)>{{ ucfirst($metodo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="observacoes" class="form-label fw-semibold">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="2" class="form-control form-control-lg">{{ old('observacoes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar pagamento</button>
                    </div>
                </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Método</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($conta->pagamentos as $pagamento)
                            <tr>
                                <td class="px-4 py-3">{{ optional($pagamento->data_pagamento)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($pagamento->metodo) }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <form method="POST" action="{{ route('contas.pagar.pagamentos.destroy', $pagamento) }}" onsubmit="return confirm('Deseja estornar este pagamento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Estornar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-5 text-center text-body-secondary">Sem pagamentos lançados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
