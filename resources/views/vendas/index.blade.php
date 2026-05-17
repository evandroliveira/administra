<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Comercial</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Vendas</h2>
                <p class="text-body-secondary mb-0">Acompanhe o funil de vendas, exportações e emissão fiscal em um único painel.</p>
            </div>
            <a href="{{ route('vendas.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">Nova venda</a>
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
                <form method="GET" action="{{ route('vendas.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select id="status" name="status" class="form-select form-select-lg">
                        <option value="">Todos os status</option>
                        @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                            <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <label for="categoria_id" class="form-label fw-semibold">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-select form-select-lg">
                            <option value="">Todas as categorias</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(($filtros['categoria_id'] ?? '') === (string) $categoria->id)>{{ $categoria->nome }}{{ $categoria->ativo ? '' : ' (inativa)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2 d-grid">
                        <button type="submit" class="btn btn-dark btn-lg rounded-pill">Filtrar</button>
                    </div>
                    <div class="col-sm-6 col-lg-2 d-grid">
                        <a href="{{ route('vendas.index') }}" class="btn btn-light btn-lg rounded-pill">Limpar</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap items-center gap-2">
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
                            <th class="px-4 py-3">Número</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Status</th>
                            @if ($fiscalHabilitada)
                                <th class="px-4 py-3">Nota Fiscal</th>
                            @endif
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($vendas as $venda)
                            <tr>
                                <td class="px-4 py-3 fw-semibold text-dark">#{{ $venda->numero }}</td>
                                <td class="px-4 py-3">{{ $venda->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3">{{ optional($venda->data_venda)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($venda->status) }}</td>
                                @if ($fiscalHabilitada)
                                    <td class="px-4 py-3">
                                        @php
                                            $tone = match ($venda->status_nota_fiscal) {
                                                'emitida' => 'text-bg-success',
                                                'pendente' => 'text-bg-warning',
                                                'erro' => 'text-bg-danger',
                                                default => 'text-bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $tone }} px-3 py-2 text-uppercase">
                                            {{ str_replace('_', ' ', $venda->status_nota_fiscal ?: 'nao_emitir') }}
                                        </span>
                                        @if ($venda->nota_fiscal_numero)
                                            <div class="mt-2 text-body-secondary">Nº {{ $venda->nota_fiscal_numero }}</div>
                                        @endif
                                        @if ($venda->nota_fiscal_chave)
                                            <div class="text-body-tertiary">{{ \Illuminate\Support\Str::limit($venda->nota_fiscal_chave, 18) }}</div>
                                        @endif
                                        @if ($venda->nota_fiscal_mensagem && ! $venda->notaFiscalEmitida())
                                            <div class="mt-1 text-body-secondary">{{ $venda->nota_fiscal_mensagem }}</div>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3">R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                        <form method="POST" action="{{ route('vendas.status.update', $venda) }}" class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="next" value="{{ request()->getRequestUri() }}">
                                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $statusOpcao)
                                                    <option value="{{ $statusOpcao }}" @selected($venda->status === $statusOpcao)>{{ ucfirst($statusOpcao) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">Atualizar</button>
                                        </form>

                                        @if ($fiscalHabilitada && $venda->podeEmitirNotaFiscal())
                                            <form method="POST" action="{{ route('vendas.nota-fiscal.emitir', $venda) }}">
                                                @csrf
                                                <input type="hidden" name="next" value="{{ request()->getRequestUri() }}">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">Emitir NF</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('vendas.show', $venda) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detalhes</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $fiscalHabilitada ? 7 : 6 }}" class="px-4 py-5 text-center text-body-secondary">Nenhuma venda encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>
                {{ $vendas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
