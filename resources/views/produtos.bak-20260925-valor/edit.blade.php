<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Editar Produto</h2>
            <p class="text-body-secondary mb-0">Atualize dados comerciais e de estoque do item selecionado.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="mx-auto" style="max-width: 980px;">
            @include('produtos._form', ['action' => route('produtos.update', $produto), 'method' => 'PUT', 'produto' => $produto, 'categorias' => $categorias])
        </div>
    </div>
</x-app-layout>
