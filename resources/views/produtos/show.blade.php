<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Produto</h2></x-slot>
    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <div><dt class="font-semibold">Código</dt><dd>{{ $produto->codigo }}</dd></div>
                    <div><dt class="font-semibold">Nome</dt><dd>{{ $produto->nome }}</dd></div>
                    <div><dt class="font-semibold">Categoria</dt><dd>{{ $produto->categoria->nome ?? '-' }}</dd></div>
                    <div><dt class="font-semibold">Preço custo</dt><dd>R$ {{ number_format((float) $produto->preco_custo, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Preço venda</dt><dd>R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Margem</dt><dd>{{ number_format((float) $produto->margem_lucro, 2, ',', '.') }}%</dd></div>
                    <div><dt class="font-semibold">Estoque atual</dt><dd>{{ $produto->estoque_atual }}</dd></div>
                    <div><dt class="font-semibold">Estoque mínimo</dt><dd>{{ $produto->estoque_minimo }}</dd></div>
                    <div><dt class="font-semibold">Ativo</dt><dd>{{ $produto->ativo ? 'Sim' : 'Não' }}</dd></div>
                </dl>
            </div>
            <div class="flex gap-2"><a href="{{ route('produtos.edit', $produto) }}" class="px-4 py-2 bg-amber-600 text-white rounded-md">Editar</a><a href="{{ route('produtos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Voltar</a></div>
        </div>
    </div>
</x-app-layout>
