<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Contas a Receber</h2>
            <p class="text-body-secondary mb-0">Acompanhe vencimentos, recebimentos e exportações do contas a receber.</p>
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
                <div class="d-flex flex-column gap-4">
                    <form method="GET" action="{{ route('contas.receber.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select form-select-lg">
                                <option value="">Todos</option>
                                @foreach (['aberta', 'parcial', 'quitada', 'vencida', 'cancelada'] as $status)
                                    <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="vencimento_inicio" class="form-label fw-semibold">Vencimento início</label>
                            <input id="vencimento_inicio" type="date" name="vencimento_inicio" value="{{ $filtros['vencimento_inicio'] ?? '' }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-md-3">
                            <label for="forma_recebimento" class="form-label fw-semibold">Forma</label>
                            <select id="forma_recebimento" name="forma_recebimento" class="form-select form-select-lg">
                                <option value="">Todas</option>
                                @foreach ($formasRecebimento as $formaRecebimento => $label)
                                    <option value="{{ $formaRecebimento }}" @selected(($filtros['forma_recebimento'] ?? '') === $formaRecebimento)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="vencimento_fim" class="form-label fw-semibold">Vencimento fim</label>
                            <input id="vencimento_fim" type="date" name="vencimento_fim" value="{{ $filtros['vencimento_fim'] ?? '' }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-1 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill">Filtrar</button>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-2 d-grid">
                            <a href="{{ route('contas.receber.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
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
                            <th class="px-4 py-3">Vencimento</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Forma</th>
                            <th class="px-4 py-3">Saldo</th>
                            <th class="px-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($contas as $conta)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">{{ $conta->id }}</td>
                                <td class="px-4 py-3">{{ $conta->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3">{{ optional($conta->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($conta->status) }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $formaTone = match ($conta->forma_recebimento) {
                                            'avista' => 'text-bg-success',
                                            'boleto' => 'text-bg-primary',
                                            'promissoria' => 'text-bg-warning',
                                            'conta' => 'text-bg-secondary',
                                            default => 'text-bg-light',
                                        };
                                    @endphp
                                    <span class="badge {{ $formaTone }} px-3 py-2">{{ $conta->forma_recebimento_label }}</span>
                                </td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                        @if ($conta->forma_recebimento === 'boleto' && $conta->possui_boleto)
                                            <a href="{{ $conta->boleto_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success rounded-pill">Abrir boleto</a>
                                        @endif
                                        <a href="{{ route('contas.receber.show', $conta) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-center text-body-secondary">Nenhuma conta encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>
                {{ $contas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
