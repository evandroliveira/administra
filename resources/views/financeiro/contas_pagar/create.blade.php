<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova Conta a Pagar</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('contas.pagar.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <input type="text" name="descricao" required maxlength="200" value="{{ old('descricao') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                        <input type="text" name="fornecedor" required maxlength="200" value="{{ old('fornecedor') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vencimento</label>
                        <input type="date" name="data_vencimento" required value="{{ old('data_vencimento') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valor original</label>
                        <input type="number" step="0.01" min="0.01" name="valor_original" required value="{{ old('valor_original') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Juros</label>
                        <input type="number" step="0.01" min="0" name="valor_juros" value="{{ old('valor_juros', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Salvar</button>
                        <a href="{{ route('contas.pagar.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
