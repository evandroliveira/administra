<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Promissória #{{ $promissoria->id }}</h2>
            <p class="text-body-secondary mb-0">Consulte parcelas, saldo financiado e situação do título promissório.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-3 small">
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Cliente</div><div class="text-dark">{{ $promissoria->cliente->nome ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Venda</div><div class="text-dark">#{{ $promissoria->venda_numero }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Status</div><div class="text-dark">{{ ucfirst($promissoria->status) }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Valor financiado</div><div class="text-dark">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Entrada</div><div class="text-dark">R$ {{ number_format((float) $promissoria->valor_entrada, 2, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Parcelas</div><div class="text-dark">{{ $promissoria->quantidade_parcelas }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">Parcela</th>
                            <th class="px-4 py-3">Vencimento</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3">Pago</th>
                            <th class="px-4 py-3">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($promissoria->parcelas as $parcela)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">{{ $parcela->numero }}</td>
                                <td class="px-4 py-3">{{ optional($parcela->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($parcela->status) }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $parcela->valor_original, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $parcela->valor_pago, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $parcela->saldo_devedor, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-5 text-center text-body-secondary">Sem parcelas cadastradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
