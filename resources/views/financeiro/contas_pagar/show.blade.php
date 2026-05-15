<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conta a Pagar #{{ $conta->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <div><dt class="font-semibold">Fornecedor</dt><dd>{{ $conta->fornecedor }}</dd></div>
                    <div><dt class="font-semibold">Status</dt><dd>{{ ucfirst($conta->status) }}</dd></div>
                    <div><dt class="font-semibold">Vencimento</dt><dd>{{ optional($conta->data_vencimento)->format('d/m/Y') }}</dd></div>
                    <div><dt class="font-semibold">Valor original</dt><dd>R$ {{ number_format((float) $conta->valor_original, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Valor pago</dt><dd>R$ {{ number_format((float) $conta->valor_pago, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Saldo</dt><dd>R$ {{ number_format((float) $conta->saldo_devedor, 2, ',', '.') }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Registrar Pagamento</h3>
                <form method="POST" action="{{ route('contas.pagar.pagamentos.store', $conta) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valor</label>
                        <input type="number" step="0.01" min="0.01" name="valor" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value="{{ old('valor') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Método</label>
                        <select name="metodo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (['dinheiro', 'cheque', 'cartao', 'pix', 'transferencia', 'outro'] as $metodo)
                                <option value="{{ $metodo }}" @selected(old('metodo', 'transferencia') === $metodo)>{{ ucfirst($metodo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Salvar pagamento</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($conta->pagamentos as $pagamento)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($pagamento->data_pagamento)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($pagamento->metodo) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <form method="POST" action="{{ route('contas.pagar.pagamentos.destroy', $pagamento) }}" onsubmit="return confirm('Deseja estornar este pagamento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Estornar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Sem pagamentos lançados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
