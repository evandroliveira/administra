<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Editar Categoria</h2>
            <p class="text-body-secondary mb-0">Atualize nome, descricao e status da categoria selecionada.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="mx-auto" style="max-width: 820px;">
            @include('categorias._form', ['action' => route('categorias.update', $categoria), 'method' => 'PUT', 'categoria' => $categoria])
        </div>
    </div>
</x-app-layout>