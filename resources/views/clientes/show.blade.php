<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Cliente</h2></x-slot>
    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                    <div><dt class="font-semibold">Nome</dt><dd>{{ $cliente->nome }}</dd></div>
                    <div><dt class="font-semibold">Tipo</dt><dd>{{ $cliente->tipo }}</dd></div>
                    <div><dt class="font-semibold">Documento</dt><dd>{{ $cliente->cpf_cnpj }}</dd></div>
                    <div><dt class="font-semibold">Telefone</dt><dd>{{ $cliente->telefone }}</dd></div>
                    <div><dt class="font-semibold">E-mail</dt><dd>{{ $cliente->email }}</dd></div>
                    <div><dt class="font-semibold">Cidade/UF</dt><dd>{{ $cliente->cidade }}/{{ $cliente->estado }}</dd></div>
                    <div><dt class="font-semibold">Limite</dt><dd>R$ {{ number_format((float) $cliente->limite_credito, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Crédito disponível</dt><dd>R$ {{ number_format((float) $cliente->credito_disponivel, 2, ',', '.') }}</dd></div>
                    <div><dt class="font-semibold">Ativo</dt><dd>{{ $cliente->ativo ? 'Sim' : 'Não' }}</dd></div>
                </dl>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clientes.edit', $cliente) }}" class="px-4 py-2 bg-amber-600 text-white rounded-md">Editar</a>
                <a href="{{ route('clientes.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Voltar</a>
            </div>
        </div>
    </div>
</x-app-layout>
