<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Novo Produto</h2>
            <p class="text-body-secondary mb-0">Cadastre itens do catalogo com precos, estoque e categoria vinculada.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="mx-auto d-flex flex-column gap-4" style="max-width: 980px;">
            <div class="alert alert-info rounded-4 mb-0">
                Produtos cadastrados: {{ $resumoPlano['produtos_cadastrados'] }} / {{ $resumoPlano['limite_produtos'] }}.
                Restam {{ $resumoPlano['produtos_restantes'] }} vaga(s) no plano atual.
            </div>

            @if ($resumoPlano['limite_atingido'])
                <div class="alert alert-warning rounded-4 mb-0">
                    O plano atual permite até {{ $resumoPlano['limite_produtos'] }} produto(s). Faça upgrade para cadastrar mais itens.
                    <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                        <a href="{{ route('assinatura.show') }}" class="btn btn-warning rounded-pill px-4">Gerenciar plano</a>
                        <a href="{{ route('produtos.index') }}" class="btn btn-light rounded-pill px-4">Voltar para produtos</a>
                    </div>
                </div>
            @else
                @include('produtos._form', ['action' => route('produtos.store'), 'method' => 'POST', 'produto' => null, 'categorias' => $categorias])
            @endif
        </div>
    </div>
</x-app-layout>
