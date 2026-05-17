<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Conta a Receber #{{ $conta->id }}</h2>
            <p class="text-body-secondary mb-0">Veja saldo, promissória vinculada e lançamentos de pagamento.</p>
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
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Cliente</div><div class="text-dark">{{ $conta->cliente->nome ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Venda</div><div class="text-dark">#{{ $conta->venda_numero }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Status</div><div class="text-dark">{{ ucfirst($conta->status) }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Valor original</div><div class="text-dark">R$ {{ number_format((float) $conta->valor_original, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Valor pago</div><div class="text-dark">R$ {{ number_format((float) $conta->valor_pago, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Saldo</div><div class="text-dark">R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <h3 class="h4 fw-semibold text-dark mb-4">Registrar Pagamento</h3>
                <form method="POST" action="{{ route('contas.receber.pagamentos.store', $conta) }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="valor" class="form-label fw-semibold">Valor</label>
                        <input id="valor" type="number" step="0.01" min="0.01" name="valor" required class="form-control form-control-lg" value="{{ old('valor') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="valor_abatimento" class="form-label fw-semibold">Abatimento</label>
                        <input id="valor_abatimento" type="number" step="0.01" min="0" name="valor_abatimento" class="form-control form-control-lg" value="{{ old('valor_abatimento', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="metodo" class="form-label fw-semibold">Método</label>
                        <select id="metodo" name="metodo" class="form-select form-select-lg">
                            @foreach (['dinheiro', 'cheque', 'cartao', 'pix', 'transferencia', 'outro'] as $metodo)
                                <option value="{{ $metodo }}" @selected(old('metodo', 'dinheiro') === $metodo)>{{ ucfirst($metodo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($conta->promissoria)
                        <div class="col-md-6">
                            <label for="promissoria_parcela_id" class="form-label fw-semibold">Parcela da promissória</label>
                            <select id="promissoria_parcela_id" name="promissoria_parcela_id" class="form-select form-select-lg">
                                <option value="">Sem parcela</option>
                                @foreach ($conta->promissoria->parcelas as $parcela)
                                    <option value="{{ $parcela->id }}" @selected((int) old('promissoria_parcela_id') === (int) $parcela->id)>
                                        Parcela {{ $parcela->numero }} - Saldo R$ {{ number_format((float) $parcela->saldo_devedor, 2, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12">
                        <label for="observacoes" class="form-label fw-semibold">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="2" class="form-control form-control-lg">{{ old('observacoes') }}</textarea>
                    </div>
                    <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-3 border-top mt-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar pagamento</button>
                        <a href="{{ route('contas.receber.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Voltar</a>
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
                            <th class="px-4 py-3">Multa/Juros</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($conta->pagamentos as $pagamento)
                            <tr>
                                <td class="px-4 py-3">{{ optional($pagamento->data_pagamento)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($pagamento->metodo) }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $pagamento->valor_multa + (float) $pagamento->valor_juros, 2, ',', '.') }}</td>
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
