<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Venda #{{ $venda->numero }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <dl class="grid flex-1 grid-cols-1 gap-4 text-sm text-gray-700 md:grid-cols-4">
                        <div><dt class="font-semibold">Cliente</dt><dd>{{ $venda->cliente->nome ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Status</dt><dd>{{ ucfirst($venda->status) }}</dd></div>
                        <div><dt class="font-semibold">Data</dt><dd>{{ optional($venda->data_venda)->format('d/m/Y H:i') }}</dd></div>
                        <div><dt class="font-semibold">Vendedor</dt><dd>{{ $venda->vendedor?->user?->name ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Subtotal</dt><dd>R$ {{ number_format((float) $venda->subtotal, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Desconto</dt><dd>R$ {{ number_format((float) $venda->desconto, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Frete</dt><dd>R$ {{ number_format((float) $venda->frete, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Total</dt><dd>R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</dd></div>
                    </dl>

                    <div class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 lg:max-w-sm">
                        <h3 class="font-semibold text-slate-800">Atualizar status</h3>
                        <p class="mt-1 text-sm text-slate-500">Ao mover a venda para confirmada ou concluída, a nota fiscal pendente pode ser emitida automaticamente.</p>

                        <form method="POST" action="{{ route('vendas.status.update', $venda) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')

                            <label class="block text-sm font-medium text-slate-700">
                                Novo status
                                <select name="status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                                        <option value="{{ $status }}" @selected($venda->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <button type="submit" class="inline-flex items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Salvar status</button>
                        </form>
                    </div>
                </div>
            </div>

            @if ($fiscalHabilitada)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Nota Fiscal</h3>
                            @php
                                $tone = match ($venda->status_nota_fiscal) {
                                    'emitida' => 'bg-emerald-100 text-emerald-700',
                                    'pendente' => 'bg-amber-100 text-amber-700',
                                    'erro' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $tone }}">
                                {{ str_replace('_', ' ', $venda->status_nota_fiscal ?: 'nao_emitir') }}
                            </span>
                        </div>

                        @if ($venda->podeEmitirNotaFiscal())
                            <form method="POST" action="{{ route('vendas.nota-fiscal.emitir', $venda) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">Emitir nota fiscal</button>
                            </form>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-700">
                        <div><dt class="font-semibold">Número</dt><dd>{{ $venda->nota_fiscal_numero ?: '-' }}</dd></div>
                        <div><dt class="font-semibold">Série</dt><dd>{{ $venda->nota_fiscal_serie ?: '-' }}</dd></div>
                        <div><dt class="font-semibold">Protocolo</dt><dd>{{ $venda->nota_fiscal_protocolo ?: '-' }}</dd></div>
                        <div><dt class="font-semibold">Emitida em</dt><dd>{{ optional($venda->nota_fiscal_emitida_em)->format('d/m/Y H:i') ?: '-' }}</dd></div>
                        <div class="md:col-span-2"><dt class="font-semibold">Chave</dt><dd class="break-all">{{ $venda->nota_fiscal_chave ?: '-' }}</dd></div>
                        <div><dt class="font-semibold">PDF</dt><dd>@if ($venda->nota_fiscal_url_pdf)<a href="{{ $venda->nota_fiscal_url_pdf }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">Abrir PDF</a>@else-@endif</dd></div>
                        <div><dt class="font-semibold">XML</dt><dd>@if ($venda->nota_fiscal_url_xml)<a href="{{ $venda->nota_fiscal_url_xml }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">Abrir XML</a>@else-@endif</dd></div>
                    </dl>

                    @if ($venda->nota_fiscal_mensagem)
                        <p class="mt-4 text-sm text-gray-700"><strong>Mensagem:</strong> {{ $venda->nota_fiscal_mensagem }}</p>
                    @endif
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qtd</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preço</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lucro</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach ($venda->itens as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->produto->nome ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->quantidade }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $item->lucro, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($venda->contaReceber)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Conta a Receber Gerada</h3>
                    <p class="text-sm text-gray-700">Status: <strong>{{ ucfirst($venda->contaReceber->status) }}</strong></p>
                    <p class="text-sm text-gray-700">Vencimento: <strong>{{ optional($venda->contaReceber->data_vencimento)->format('d/m/Y') }}</strong></p>
                    <p class="text-sm text-gray-700">Saldo: <strong>R$ {{ number_format((float) $venda->contaReceber->saldo_devedor, 2, ',', '.') }}</strong></p>
                    <div class="mt-3 flex gap-4">
                        <a href="{{ route('contas.receber.show', $venda->contaReceber) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Abrir conta a receber</a>
                        @if ($venda->contaReceber->pagamentos->isNotEmpty())
                            <a href="{{ route('vendas.recibo', $venda) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Imprimir recibo</a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($venda->promissoria)
                @php($proximaParcela = $venda->promissoria->parcelas->first(fn ($parcela) => (float) $parcela->saldo_devedor > 0))
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Promissória</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-700">
                        <div><dt class="font-semibold">Entrada</dt><dd>R$ {{ number_format((float) $venda->promissoria->valor_entrada, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Financiado</dt><dd>R$ {{ number_format((float) $venda->promissoria->valor_financiado, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Parcelas</dt><dd>{{ $venda->promissoria->parcelas->count() }}</dd></div>
                        <div><dt class="font-semibold">Status</dt><dd>{{ ucfirst($venda->promissoria->status) }}</dd></div>
                        <div><dt class="font-semibold">Multa</dt><dd>{{ number_format((float) $venda->promissoria->percentual_multa_atraso, 2, ',', '.') }}%</dd></div>
                        <div><dt class="font-semibold">Juros ao dia</dt><dd>{{ number_format((float) $venda->promissoria->percentual_juros_dia, 4, ',', '.') }}%</dd></div>
                        <div><dt class="font-semibold">Próximo vencimento</dt><dd>{{ optional($proximaParcela?->data_vencimento)->format('d/m/Y') ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Saldo</dt><dd>R$ {{ number_format((float) $venda->promissoria->saldo_devedor, 2, ',', '.') }}</dd></div>
                    </dl>
                    @if ($venda->promissoria->observacoes)
                        <p class="mt-3 text-sm text-gray-700"><strong>Observações:</strong> {{ $venda->promissoria->observacoes }}</p>
                    @endif
                    <div class="mt-3 flex gap-4">
                        @if (auth()->user()?->hasAnyRole(['admin', 'gerente']))
                            <a href="{{ route('promissorias.show', $venda->promissoria) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Abrir promissória</a>
                        @endif
                        <a href="{{ route('vendas.promissoria.imprimir', $venda) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Imprimir promissória</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
