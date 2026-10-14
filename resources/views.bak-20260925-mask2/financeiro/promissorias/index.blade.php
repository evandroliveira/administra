<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Promissórias</h2>
            <p class="text-body-secondary mb-0">Filtre, exporte e acompanhe títulos promissórios emitidos pela empresa.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column gap-4">
                    <form method="GET" action="{{ route('promissorias.index') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('promissorias.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($exportXlsxUrl)
                            <a href="{{ $exportXlsxUrl }}" class="btn btn-outline-secondary rounded-pill">Exportar XLSX</a>
                        @endif
                        @if ($exportPdfUrl)
                            <a href="{{ $exportPdfUrl }}" class="btn btn-outline-secondary rounded-pill">Exportar PDF</a>
                        @endif
                        <a href="{{ $exportCsvUrl }}" class="btn btn-dark rounded-pill">Exportar CSV</a>
                    </div>
                </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Financiado</th>
                            <th class="px-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($promissorias as $promissoria)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">{{ $promissoria->id }}</td>
                                <td class="px-4 py-3">{{ $promissoria->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3">{{ ucfirst($promissoria->status) }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end"><a href="{{ route('promissorias.show', $promissoria) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-5 text-center text-body-secondary">Nenhuma promissória encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $promissorias->links() }}</div>
        </div>
    </div>
</x-app-layout>
