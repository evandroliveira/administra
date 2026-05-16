<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Produto</h2></x-slot>
    <div class="py-8">
        <div class="max-w-4xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                Produtos cadastrados: {{ $resumoPlano['produtos_cadastrados'] }} / {{ $resumoPlano['limite_produtos'] }}.
                Restam {{ $resumoPlano['produtos_restantes'] }} vaga(s) no plano atual.
            </div>

            @if ($resumoPlano['limite_atingido'])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    O plano atual permite até {{ $resumoPlano['limite_produtos'] }} produto(s). Faça upgrade para cadastrar mais itens.
                    <div class="mt-3 flex gap-3">
                        <a href="{{ route('assinatura.show') }}" class="px-4 py-2 bg-amber-600 text-white rounded-md">Gerenciar plano</a>
                        <a href="{{ route('produtos.index') }}" class="px-4 py-2 bg-white text-amber-900 border border-amber-300 rounded-md">Voltar para produtos</a>
                    </div>
                </div>
            @else
                @include('produtos._form', ['action' => route('produtos.store'), 'method' => 'POST', 'produto' => null, 'categorias' => $categorias])
            @endif
        </div>
    </div>
</x-app-layout>
