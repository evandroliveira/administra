<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Promissória #{{ $promissoria->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <div><dt class="font-semibold">Cliente</dt><dd>{{ $promissoria->cliente->nome ?? '-' }}</dd></div>
                    <div><dt class="font-semibold">Venda</dt><dd>#{{ $promissoria->venda_numero }}</dd></div>
                    <div><dt class="font-semibold">Status</dt><dd>{{ ucfirst($promissoria->status) }}</dd></div>
                    <div><dt class="font-semibold">Valor financiado</dt><dd>R$ {{ number_format((float) $promissoria->valor_financiado, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Entrada</dt><dd>R$ {{ number_format((float) $promissoria->valor_entrada, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Parcelas</dt><dd>{{ $promissoria->quantidade_parcelas }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parcela</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($promissoria->parcelas as $parcela)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $parcela->numero }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($parcela->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($parcela->status) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $parcela->valor_original, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $parcela->valor_pago, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $parcela->saldo_devedor, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Sem parcelas cadastradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
