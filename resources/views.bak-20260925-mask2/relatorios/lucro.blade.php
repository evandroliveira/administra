<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-xl-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Relatórios</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Relatório de Lucro</h2>
                <p class="text-body-secondary mb-0">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ $hubUrl }}" class="btn btn-light rounded-pill border">Central</a>
                <a href="{{ route('relatorios.faturamento') }}" class="btn btn-light rounded-pill border">Faturamento</a>
                <a href="{{ route('relatorios.inadimplentes') }}" class="btn btn-light rounded-pill border">Inadimplentes</a>
                @if ($exportXlsxUrl)
                    <a href="{{ $exportXlsxUrl }}" class="btn btn-outline-secondary rounded-pill">Exportar XLSX</a>
                @endif
                @if ($exportPdfUrl)
                    <a href="{{ $exportPdfUrl }}" class="btn btn-outline-secondary rounded-pill">Exportar PDF</a>
                @endif
                <a href="{{ $exportCsvUrl }}" class="btn btn-dark rounded-pill">Exportar CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                <form method="GET" action="{{ route('relatorios.lucro') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="data_inicio" class="form-label fw-semibold">Data inicial</label>
                        <input id="data_inicio" type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="form-label fw-semibold">Data final</label>
                        <input id="data_fim" type="date" name="data_fim" value="{{ $dataFim }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6 d-flex flex-column flex-lg-row align-items-lg-end gap-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Atualizar</button>
                        <span class="text-body-secondary mb-lg-2">Considera apenas vendas confirmadas e concluídas.</span>
                    </div>
                </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <div class="small text-uppercase text-body-secondary fw-semibold">Lucro total do período</div>
                <div class="display-6 fw-semibold text-dark mt-2">R$ {{ number_format($totalLucro, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2">
                    <h3 class="h4 fw-semibold text-dark mb-0">Lucro por Produto</h3>
                    <span class="text-body-secondary">{{ $dataInicio }} até {{ $dataFim }}</span>
                </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Produto</th>
                                <th class="px-4 py-3">Quantidade Vendida</th>
                                <th class="px-4 py-3">Lucro</th>
                                <th class="px-4 py-3">% do Total</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($lucroPorProduto as $linha)
                                <tr>
                                    <td class="px-4 py-3">{{ $linha['produto'] }}</td>
                                    <td class="px-4 py-3">{{ $linha['quantidade'] }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format($linha['total_lucro'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ number_format($linha['percentual_total'], 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-5 text-center text-body-secondary">Nenhum item vendido no período filtrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>