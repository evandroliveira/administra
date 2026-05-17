<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-xl-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Relatórios</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Relatório de Faturamento</h2>
                <p class="text-body-secondary mb-0">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ $hubUrl }}" class="btn btn-light rounded-pill border">Central</a>
                <a href="{{ route('relatorios.inadimplentes') }}" class="btn btn-light rounded-pill border">Inadimplentes</a>
                <a href="{{ route('relatorios.lucro') }}" class="btn btn-light rounded-pill border">Lucro</a>
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
                <form method="GET" action="{{ route('relatorios.faturamento') }}" class="row g-3 align-items-end">
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
                        <span class="text-body-secondary mb-lg-2">Somente vendas confirmadas e concluídas entram no cálculo.</span>
                    </div>
                </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Faturamento</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">R$ {{ number_format($resumo['total_faturamento'], 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Lucro</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">R$ {{ number_format($resumo['total_lucro'], 2, ',', '.') }}</div>
                    </div></div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Vendas</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumo['quantidade_vendas'] }}</div>
                    </div></div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Promissórias</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumo['quantidade_promissorias'] }}</div>
                    </div></div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Financiado</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">R$ {{ number_format($resumo['total_financiado_promissorias'], 2, ',', '.') }}</div>
                    <div class="text-body-secondary mt-2">Entradas: R$ {{ number_format($resumo['total_entrada_promissorias'], 2, ',', '.') }}</div>
                    </div></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2">
                    <h3 class="h4 fw-semibold text-dark mb-0">Vendas por dia</h3>
                    <span class="text-body-secondary">{{ $dataInicio }} até {{ $dataFim }}</span>
                </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Quantidade</th>
                                <th class="px-4 py-3">Faturamento</th>
                                <th class="px-4 py-3">Lucro</th>
                                <th class="px-4 py-3">Clientes</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($vendasPorDia as $linha)
                                <tr>
                                    <td class="px-4 py-3">{{ $linha['data']?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $linha['quantidade'] }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format($linha['total'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format($linha['lucro'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ $linha['clientes']->implode(', ') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-5 text-center text-body-secondary">Nenhuma venda confirmada ou concluída no período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2">
                    <h3 class="h4 fw-semibold text-dark mb-0">Promissórias do período</h3>
                    <span class="text-body-secondary">{{ $promissorias->count() }} registros</span>
                </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Entrada</th>
                                <th class="px-4 py-3">Financiado</th>
                                <th class="px-4 py-3">Multa</th>
                                <th class="px-4 py-3">Juros/dia</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($promissorias as $promissoria)
                                <tr>
                                    <td class="px-4 py-3">{{ optional($promissoria->venda?->data_venda)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $promissoria->numero_documento }}</td>
                                    <td class="px-4 py-3">{{ $promissoria->cliente?->nome }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $promissoria->valor_entrada, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') }}%</td>
                                    <td class="px-4 py-3">{{ number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') }}%/dia</td>
                                    <td class="px-4 py-3">{{ ucfirst($promissoria->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-5 text-center text-body-secondary">Nenhuma promissória encontrada no período filtrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
