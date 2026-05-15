<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clientes</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="flex justify-between items-center">
                <form method="GET" action="{{ route('clientes.index') }}" class="flex gap-2">
                    <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nome, documento ou e-mail" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm">Buscar</button>
                </form>
                <a href="{{ route('clientes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Novo cliente</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limite</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $cliente->nome }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $cliente->cpf_cnpj }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $cliente->telefone }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">R$ {{ number_format((float) $cliente->limite_credito, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm space-x-3">
                                    <a href="{{ route('clientes.show', $cliente) }}" class="text-indigo-600">Detalhes</a>
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="text-amber-600">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum cliente encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $clientes->links() }}</div>
        </div>
    </div>
</x-app-layout>
