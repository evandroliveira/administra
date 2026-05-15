<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contas a Receber</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <form method="GET" action="{{ route('contas.receber.index') }}" class="grid flex-1 grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Todos</option>
                                @foreach (['aberta', 'parcial', 'quitada', 'vencida', 'cancelada'] as $status)
                                    <option value="{{ $status }}" @selected(($filtros['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vencimento início</label>
                            <input type="date" name="vencimento_inicio" value="{{ $filtros['vencimento_inicio'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vencimento fim</label>
                            <input type="date" name="vencimento_fim" value="{{ $filtros['vencimento_fim'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md">Filtrar</button>
                            <a href="{{ route('contas.receber.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Limpar</a>
                        </div>
                    </form>

                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <a href="{{ $exportXlsxUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar XLSX</a>
                        <a href="{{ $exportPdfUrl }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm">Exportar PDF</a>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($contas as $conta)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $conta->id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $conta->cliente->nome ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($conta->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($conta->status) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('contas.receber.show', $conta) }}" class="text-indigo-600 hover:text-indigo-900">Detalhes</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Nenhuma conta encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $contas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
