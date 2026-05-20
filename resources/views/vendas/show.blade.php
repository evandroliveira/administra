<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Comercial</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Venda #{{ $venda->numero }}</h2>
            <p class="text-body-secondary mb-0">Detalhe da venda, conta gerada, promissória e situação fiscal.</p>
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
                <div class="row g-4">
                    <div class="col-lg-8">
                    <dl class="row g-3 small mb-0">
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Cliente</dt><dd class="mb-0 text-dark">{{ $venda->cliente->nome ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Status</dt><dd class="mb-0 text-dark">{{ ucfirst($venda->status) }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Data</dt><dd class="mb-0 text-dark">{{ optional($venda->data_venda)->format('d/m/Y H:i') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Vendedor</dt><dd class="mb-0 text-dark">{{ $venda->vendedor?->user?->name ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Subtotal</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->subtotal, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Desconto</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->desconto, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Frete</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->frete, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Total</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</dd></div></div>
                    </dl>
                    </div>

                    <div class="col-lg-4">
                    <div class="border rounded-4 bg-light-subtle p-4 h-100">
                        <h3 class="h5 fw-semibold text-dark mb-2">Atualizar status</h3>
                        <p class="text-body-secondary mb-0">Ao mover a venda para confirmada ou concluída, a nota fiscal pendente pode ser emitida automaticamente.</p>

                        <form method="POST" action="{{ route('vendas.status.update', $venda) }}" class="mt-4 d-flex flex-column gap-3">
                            @csrf
                            @method('PATCH')

                            <label for="status-update" class="form-label fw-semibold text-dark mb-0">
                                Novo status
                                <select id="status-update" name="status" class="form-select form-select-lg mt-2">
                                    @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                                        <option value="{{ $status }}" @selected($venda->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <button type="submit" class="btn btn-dark btn-lg rounded-pill align-self-start px-4">Salvar status</button>
                        </form>
                    </div>
                    </div>
                </div>
                </div>
            </div>

            @if ($fiscalHabilitada)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3">
                        <div>
                            <h3 class="h4 fw-semibold text-dark mb-3">Nota Fiscal</h3>
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
                        </div>

                        @if ($venda->podeEmitirNotaFiscal())
                            <form method="POST" action="{{ route('vendas.nota-fiscal.emitir', $venda) }}">
                                @csrf
                                <button type="submit" class="btn btn-success rounded-pill px-4">Emitir nota fiscal</button>
                            </form>
                        @endif
                    </div>

                    <dl class="row g-3 small mt-1 mb-0">
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Número</dt><dd class="mb-0 text-dark">{{ $venda->nota_fiscal_numero ?: '-' }}</dd></div></div>
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Série</dt><dd class="mb-0 text-dark">{{ $venda->nota_fiscal_serie ?: '-' }}</dd></div></div>
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Protocolo</dt><dd class="mb-0 text-dark">{{ $venda->nota_fiscal_protocolo ?: '-' }}</dd></div></div>
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Emitida em</dt><dd class="mb-0 text-dark">{{ optional($venda->nota_fiscal_emitida_em)->format('d/m/Y H:i') ?: '-' }}</dd></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Chave</dt><dd class="mb-0 text-dark text-break">{{ $venda->nota_fiscal_chave ?: '-' }}</dd></div></div>
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">PDF</dt><dd class="mb-0">@if ($venda->nota_fiscal_url_pdf)<a href="{{ $venda->nota_fiscal_url_pdf }}" target="_blank" rel="noopener" class="link-primary text-decoration-none">Abrir PDF</a>@else-@endif</dd></div></div>
                        <div class="col-md-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">XML</dt><dd class="mb-0">@if ($venda->nota_fiscal_url_xml)<a href="{{ $venda->nota_fiscal_url_xml }}" target="_blank" rel="noopener" class="link-primary text-decoration-none">Abrir XML</a>@else-@endif</dd></div></div>
                    </dl>

                    @if ($venda->nota_fiscal_mensagem)
                        <p class="small text-body-secondary mt-4 mb-0"><strong>Mensagem:</strong> {{ $venda->nota_fiscal_mensagem }}</p>
                    @endif
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="px-4 py-3">Produto</th>
                            <th class="px-4 py-3">Qtd</th>
                            <th class="px-4 py-3">Preço</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Lucro</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($venda->itens as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $item->produto->nome ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item->quantidade }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $item->lucro, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            @if ($venda->contaReceber)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                    <h3 class="h4 fw-semibold text-dark mb-3">Conta a Receber Gerada</h3>
                    <div class="row g-3 small mb-0">
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100">Status: <strong>{{ ucfirst($venda->contaReceber->status) }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100">Forma: <strong>{{ $venda->contaReceber->forma_recebimento_label }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100">Vencimento: <strong>{{ optional($venda->contaReceber->data_vencimento)->format('d/m/Y') }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100">Saldo: <strong>R$ {{ number_format((float) $venda->contaReceber->saldo_devedor, 2, ',', '.') }}</strong></div></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('contas.receber.show', $venda->contaReceber) }}" class="btn btn-outline-primary rounded-pill">Abrir conta a receber</a>
                        @if ($venda->contaReceber->forma_recebimento === 'boleto' && $venda->contaReceber->possui_boleto)
                            <a href="{{ $venda->contaReceber->boleto_url }}" target="_blank" rel="noopener" class="btn btn-outline-success rounded-pill">Abrir boleto</a>
                        @endif
                        @if ($venda->contaReceber->pagamentos->isNotEmpty())
                            <a href="{{ route('vendas.recibo', $venda) }}" class="btn btn-outline-secondary rounded-pill">Imprimir recibo</a>
                        @endif
                    </div>
                    </div>
                </div>
            @endif

            @if ($venda->promissoria)
                @php($proximaParcela = $venda->promissoria->parcelas->first(fn ($parcela) => (float) $parcela->saldo_devedor > 0))
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                    <h3 class="h4 fw-semibold text-dark mb-3">Promissória</h3>
                    <dl class="row g-3 small mb-0">
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Entrada</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->promissoria->valor_entrada, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Financiado</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->promissoria->valor_financiado, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Parcelas</dt><dd class="mb-0 text-dark">{{ $venda->promissoria->parcelas->count() }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Status</dt><dd class="mb-0 text-dark">{{ ucfirst($venda->promissoria->status) }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Multa</dt><dd class="mb-0 text-dark">{{ number_format((float) $venda->promissoria->percentual_multa_atraso, 2, ',', '.') }}%</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Juros ao dia</dt><dd class="mb-0 text-dark">{{ number_format((float) $venda->promissoria->percentual_juros_dia, 4, ',', '.') }}%</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Próximo vencimento</dt><dd class="mb-0 text-dark">{{ optional($proximaParcela?->data_vencimento)->format('d/m/Y') ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Saldo</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $venda->promissoria->saldo_devedor, 2, ',', '.') }}</dd></div></div>
                    </dl>
                    @if ($venda->promissoria->observacoes)
                        <p class="small text-body-secondary mt-3 mb-0"><strong>Observações:</strong> {{ $venda->promissoria->observacoes }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if (auth()->user()?->hasAnyRole(['admin', 'gerente']))
                            <a href="{{ route('promissorias.show', $venda->promissoria) }}" class="btn btn-outline-primary rounded-pill">Abrir promissória</a>
                        @endif
                        <a href="{{ route('vendas.promissoria.imprimir', $venda) }}" class="btn btn-outline-secondary rounded-pill">Imprimir promissória</a>
                    </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
