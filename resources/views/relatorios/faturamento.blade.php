<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Relatório de Faturamento</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $hubUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Central</a>
                <a href="{{ route('relatorios.inadimplentes') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Inadimplentes</a>
                <a href="{{ route('relatorios.lucro') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Lucro</a>
                <a href="{{ $exportXlsxUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar XLSX</a>
                <a href="{{ $exportPdfUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar PDF</a>
                <a href="{{ $exportCsvUrl }}" class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Exportar CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('relatorios.faturamento') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="data_inicio" class="block text-sm font-medium text-gray-700">Data inicial</label>
                        <input id="data_inicio" type="date" name="data_inicio" value="{{ $dataInicio }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="data_fim" class="block text-sm font-medium text-gray-700">Data final</label>
                        <input id="data_fim" type="date" name="data_fim" value="{{ $dataFim }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="md:col-span-2 flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Atualizar</button>
                        <span class="text-sm text-gray-500">Somente vendas confirmadas e concluídas entram no cálculo.</span>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Faturamento</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">R$ {{ number_format($resumo['total_faturamento'], 2, ',', '.') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Lucro</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">R$ {{ number_format($resumo['total_lucro'], 2, ',', '.') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Vendas</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $resumo['quantidade_vendas'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Promissórias</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $resumo['quantidade_promissorias'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Financiado</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">R$ {{ number_format($resumo['total_financiado_promissorias'], 2, ',', '.') }}</div>
                    <div class="mt-2 text-sm text-gray-500">Entradas: R$ {{ number_format($resumo['total_entrada_promissorias'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Vendas por dia</h3>
                    <span class="text-sm text-gray-500">{{ $dataInicio }} até {{ $dataFim }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Faturamento</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Lucro</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Clientes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
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
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhuma venda confirmada ou concluída no período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Promissórias do período</h3>
                    <span class="text-sm text-gray-500">{{ $promissorias->count() }} registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Entrada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Financiado</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Multa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Juros/dia</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
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
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhuma promissória encontrada no período filtrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
