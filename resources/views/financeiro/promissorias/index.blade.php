<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Promissórias</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <form method="GET" action="{{ route('promissorias.index') }}" class="grid flex-1 grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Todos</option>
                                @foreach (['aberta', 'parcial', 'quitada', 'vencida', 'cancelada'] as $status)
                                    <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md">Filtrar</button>
                            <a href="{{ route('promissorias.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Limpar</a>
                        </div>
                    </form>

                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        @if ($exportXlsxUrl)
                            <a href="{{ $exportXlsxUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar XLSX</a>
                        @endif
                        @if ($exportPdfUrl)
                            <a href="{{ $exportPdfUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar PDF</a>
                        @endif
                        <a href="{{ $exportCsvUrl }}" class="px-3 py-2 bg-gray-900 text-white rounded-md text-sm">Exportar CSV</a>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Financiado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($promissorias as $promissoria)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $promissoria->id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $promissoria->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($promissoria->status) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm"><a href="{{ route('promissorias.show', $promissoria) }}" class="text-indigo-600 hover:text-indigo-900">Detalhes</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Nenhuma promissória encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $promissorias->links() }}</div>
        </div>
    </div>
</x-app-layout>
