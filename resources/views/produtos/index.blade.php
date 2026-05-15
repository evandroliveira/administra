<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Produtos</h2></x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif
            <div class="flex justify-between items-center">
                <form method="GET" action="{{ route('produtos.index') }}" class="flex gap-2">
                    <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nome ou código" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm">Buscar</button>
                </form>
                <a href="{{ route('produtos.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Novo produto</a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preço venda</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estoque</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($produtos as $produto)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $produto->codigo }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $produto->nome }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $produto->estoque_atual }}</td>
                                <td class="px-4 py-3 text-right text-sm space-x-3"><a href="{{ route('produtos.show', $produto) }}" class="text-indigo-600">Detalhes</a><a href="{{ route('produtos.edit', $produto) }}" class="text-amber-600">Editar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum produto encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $produtos->links() }}</div>
        </div>
    </div>
</x-app-layout>
