<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Relatório de Lucro</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $hubUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Central</a>
                <a href="{{ route('relatorios.faturamento') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Faturamento</a>
                <a href="{{ route('relatorios.inadimplentes') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Inadimplentes</a>
                <a href="{{ $exportXlsxUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar XLSX</a>
                <a href="{{ $exportPdfUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar PDF</a>
                <a href="{{ $exportCsvUrl }}" class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Exportar CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('relatorios.lucro') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        <span class="text-sm text-gray-500">Considera apenas vendas confirmadas e concluídas.</span>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="text-xs uppercase tracking-wide text-gray-500">Lucro total do período</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900">R$ {{ number_format($totalLucro, 2, ',', '.') }}</div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Lucro por Produto</h3>
                    <span class="text-sm text-gray-500">{{ $dataInicio }} até {{ $dataFim }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Quantidade Vendida</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Lucro</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">% do Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($lucroPorProduto as $linha)
                                <tr>
                                    <td class="px-4 py-3">{{ $linha['produto'] }}</td>
                                    <td class="px-4 py-3">{{ $linha['quantidade'] }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format($linha['total_lucro'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ number_format($linha['percentual_total'], 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum item vendido no período filtrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>