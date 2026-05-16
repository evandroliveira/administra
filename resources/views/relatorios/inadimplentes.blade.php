<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Relatório de Inadimplentes</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $hubUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Central</a>
                <a href="{{ route('relatorios.faturamento') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Faturamento</a>
                <a href="{{ route('relatorios.lucro') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Lucro</a>
                @if ($exportXlsxUrl)
                    <a href="{{ $exportXlsxUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar XLSX</a>
                @endif
                @if ($exportPdfUrl)
                    <a href="{{ $exportPdfUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Exportar PDF</a>
                @endif
                <a href="{{ $exportCsvUrl }}" class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Exportar CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Clientes inadimplentes</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $resumo['total_clientes'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Títulos em atraso</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $resumo['total_titulos'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total devido</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">R$ {{ number_format($resumo['total_geral_devido'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Resumo por Cliente</h3>
                    <span class="text-sm text-gray-500">Base em {{ $hoje->format('d/m/Y') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Títulos</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Total Devido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
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
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhum cliente inadimplente encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Títulos em Atraso</h3>
                    <span class="text-sm text-gray-500">{{ $titulosInadimplentes->count() }} registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Venda</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Dias em Atraso</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Taxas</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
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
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum título vencido encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>