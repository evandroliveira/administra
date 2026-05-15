<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Cliente</h2></x-slot>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @include('clientes._form', ['action' => route('clientes.store'), 'method' => 'POST', 'cliente' => null])
        </div>
    </div>
</x-app-layout>
