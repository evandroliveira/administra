<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-xl-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Relatórios</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Relatório de Inadimplentes</h2>
                <p class="text-body-secondary mb-0">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ $hubUrl }}" class="btn btn-light rounded-pill border">Central</a>
                <a href="{{ route('relatorios.faturamento') }}" class="btn btn-light rounded-pill border">Faturamento</a>
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
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Clientes inadimplentes</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumo['total_clientes'] }}</div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Títulos em atraso</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumo['total_titulos'] }}</div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                    <div class="small text-uppercase text-body-secondary fw-semibold">Total devido</div>
                    <div class="fs-3 fw-semibold text-dark mt-2">R$ {{ number_format($resumo['total_geral_devido'], 2, ',', '.') }}</div>
                    </div></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2">
                    <h3 class="h4 fw-semibold text-dark mb-0">Resumo por Cliente</h3>
                    <span class="text-body-secondary">Base em {{ $hoje->format('d/m/Y') }}</span>
                </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Telefone</th>
                                <th class="px-4 py-3">Títulos</th>
                                <th class="px-4 py-3">Total Devido</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($clientes as $cliente)
                                <tr>
                                    <td class="px-4 py-3">{{ $cliente['nome'] }}</td>
                                    <td class="px-4 py-3">{{ $cliente['email'] ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $cliente['telefone'] ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $cliente['quantidade_titulos'] }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format($cliente['total_devido'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-5 text-center text-body-secondary">Nenhum cliente inadimplente encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2">
                    <h3 class="h4 fw-semibold text-dark mb-0">Títulos em Atraso</h3>
                    <span class="text-body-secondary">{{ $titulosInadimplentes->count() }} registros</span>
                </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Venda</th>
                                <th class="px-4 py-3">Vencimento</th>
                                <th class="px-4 py-3">Dias em Atraso</th>
                                <th class="px-4 py-3">Saldo</th>
                                <th class="px-4 py-3">Taxas</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($titulosInadimplentes as $titulo)
                                @php($promissoria = $titulo->promissoria)
                                <tr>
                                    <td class="px-4 py-3">{{ $promissoria?->numero_documento ?? sprintf('CR-%06d', $titulo->id) }}</td>
                                    <td class="px-4 py-3">{{ $titulo->cliente?->nome }}</td>
                                    <td class="px-4 py-3">{{ $titulo->venda?->numero }}</td>
                                    <td class="px-4 py-3">{{ optional($titulo->data_vencimento)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $titulo->data_vencimento ? $titulo->data_vencimento->diffInDays($hoje) : 0 }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $titulo->saldo_devedor, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($promissoria)
                                            <div>Multa: {{ number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') }}%</div>
                                            <div>Juros: {{ number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') }}%/dia</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst((string) $titulo->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-5 text-center text-body-secondary">Nenhum título vencido encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>