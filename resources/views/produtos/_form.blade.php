<div class="bg-white shadow-sm sm:rounded-lg p-6">
    @if ($errors->any())
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="block text-sm">Código</label><input name="codigo" value="{{ old('codigo', $produto->codigo ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Nome</label><input name="nome" value="{{ old('nome', $produto->nome ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div class="md:col-span-2"><label class="block text-sm">Descrição</label><textarea name="descricao" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('descricao', $produto->descricao ?? '') }}</textarea></div>
        <div><label class="block text-sm">Categoria</label><select name="categoria_id" class="mt-1 block w-full border-gray-300 rounded-md"><option value="">Sem categoria</option>@foreach ($categorias as $categoria)<option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $produto->categoria_id ?? 0) === (int) $categoria->id)>{{ $categoria->nome }}</option>@endforeach</select></div>
        <div><label class="block text-sm">Preço custo</label><input type="number" step="0.01" min="0.01" name="preco_custo" value="{{ old('preco_custo', $produto->preco_custo ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Preço venda</label><input type="number" step="0.01" min="0.01" name="preco_venda" value="{{ old('preco_venda', $produto->preco_venda ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Custo médio</label><input type="number" step="0.01" min="0" name="custo_medio" value="{{ old('custo_medio', $produto->custo_medio ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">Estoque atual</label><input type="number" min="0" name="estoque_atual" value="{{ old('estoque_atual', $produto->estoque_atual ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">Estoque mínimo</label><input type="number" min="0" name="estoque_minimo" value="{{ old('estoque_minimo', $produto->estoque_minimo ?? 10) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div class="flex items-center gap-2 pt-6"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $produto->ativo ?? true))><span class="text-sm">Ativo</span></div>
        <div class="md:col-span-2 flex gap-2 mt-2"><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Salvar</button><a href="{{ route('produtos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</a></div>
    </form>
</div>
