<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vendas</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('vendas.index') }}" class="flex flex-wrap gap-2 items-center">
                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todos os status</option>
                        @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                            <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm">Filtrar</button>
                    <a href="{{ route('vendas.index') }}" class="px-3 py-2 bg-gray-200 text-gray-800 rounded-md text-sm">Limpar</a>
                </form>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($exportXlsxUrl)
                        <a href="{{ $exportXlsxUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar XLSX</a>
                    @endif
                    @if ($exportPdfUrl)
                        <a href="{{ $exportPdfUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar PDF</a>
                    @endif
                    <a href="{{ $exportCsvUrl }}" class="px-3 py-2 bg-gray-900 text-white rounded-md text-sm">Exportar CSV</a>
                    <a href="{{ route('vendas.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Nova venda</a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            @if ($fiscalHabilitada)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nota Fiscal</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($vendas as $venda)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">#{{ $venda->numero }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $venda->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($venda->data_venda)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($venda->status) }}</td>
                                @if ($fiscalHabilitada)
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @php
                                            $tone = match ($venda->status_nota_fiscal) {
                                                'emitida' => 'bg-emerald-100 text-emerald-700',
                                                'pendente' => 'bg-amber-100 text-amber-700',
                                                'erro' => 'bg-rose-100 text-rose-700',
                                                default => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $tone }}">
                                            {{ str_replace('_', ' ', $venda->status_nota_fiscal ?: 'nao_emitir') }}
                                        </span>
                                        @if ($venda->nota_fiscal_numero)
                                            <div class="mt-2 text-xs text-gray-500">Nº {{ $venda->nota_fiscal_numero }}</div>
                                        @endif
                                        @if ($venda->nota_fiscal_chave)
                                            <div class="text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($venda->nota_fiscal_chave, 18) }}</div>
                                        @endif
                                        @if ($venda->nota_fiscal_mensagem && ! $venda->notaFiscalEmitida())
                                            <div class="mt-1 text-xs text-gray-500">{{ $venda->nota_fiscal_mensagem }}</div>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $venda->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('vendas.status.update', $venda) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="next" value="{{ request()->getRequestUri() }}">
                                            <select name="status" class="rounded-md border-gray-300 py-1 text-sm shadow-sm">
                                                @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $statusOpcao)
                                                    <option value="{{ $statusOpcao }}" @selected($venda->status === $statusOpcao)>{{ ucfirst($statusOpcao) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-slate-700 hover:text-slate-900">Atualizar</button>
                                        </form>

                                        @if ($fiscalHabilitada && $venda->podeEmitirNotaFiscal())
                                            <form method="POST" action="{{ route('vendas.nota-fiscal.emitir', $venda) }}">
                                                @csrf
                                                <input type="hidden" name="next" value="{{ request()->getRequestUri() }}">
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-900">Emitir NF</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('vendas.show', $venda) }}" class="text-indigo-600 hover:text-indigo-900">Detalhes</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $fiscalHabilitada ? 7 : 6 }}" class="px-4 py-6 text-center text-sm text-gray-500">Nenhuma venda encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $vendas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
