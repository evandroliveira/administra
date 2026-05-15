<div class="bg-white shadow-sm sm:rounded-lg p-6">
    @if ($errors->any())
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="block text-sm">Tipo</label><select name="tipo" class="mt-1 block w-full border-gray-300 rounded-md"><option value="PF" @selected(old('tipo', $cliente->tipo ?? 'PF') === 'PF')>PF</option><option value="PJ" @selected(old('tipo', $cliente->tipo ?? 'PF') === 'PJ')>PJ</option></select></div>
        <div><label class="block text-sm">Nome</label><input name="nome" value="{{ old('nome', $cliente->nome ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">E-mail</label><input name="email" type="email" value="{{ old('email', $cliente->email ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Telefone</label><input name="telefone" value="{{ old('telefone', $cliente->telefone ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Celular</label><input name="celular" value="{{ old('celular', $cliente->celular ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">CPF/CNPJ</label><input name="cpf_cnpj" value="{{ old('cpf_cnpj', $cliente->cpf_cnpj ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">RG/IE</label><input name="rg_ie" value="{{ old('rg_ie', $cliente->rg_ie ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div class="md:col-span-2"><label class="block text-sm">Endereço</label><input name="endereco" value="{{ old('endereco', $cliente->endereco ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Número</label><input name="numero" value="{{ old('numero', $cliente->numero ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Complemento</label><input name="complemento" value="{{ old('complemento', $cliente->complemento ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">Bairro</label><input name="bairro" value="{{ old('bairro', $cliente->bairro ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Cidade</label><input name="cidade" value="{{ old('cidade', $cliente->cidade ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Estado</label><input name="estado" maxlength="2" value="{{ old('estado', $cliente->estado ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">CEP</label><input name="cep" value="{{ old('cep', $cliente->cep ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md" required></div>
        <div><label class="block text-sm">Limite de crédito</label><input name="limite_credito" type="number" step="0.01" min="0" value="{{ old('limite_credito', $cliente->limite_credito ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">Multa atraso (%)</label><input name="percentual_multa_atraso_padrao" type="number" step="0.01" min="0" value="{{ old('percentual_multa_atraso_padrao', $cliente->percentual_multa_atraso_padrao ?? 2) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div><label class="block text-sm">Juros dia (%)</label><input name="percentual_juros_dia_padrao" type="number" step="0.0001" min="0" value="{{ old('percentual_juros_dia_padrao', $cliente->percentual_juros_dia_padrao ?? 0.0333) }}" class="mt-1 block w-full border-gray-300 rounded-md"></div>
        <div class="flex items-center gap-2 pt-6"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $cliente->ativo ?? true))><span class="text-sm">Ativo</span></div>
        <div class="md:col-span-2 flex gap-2 mt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Salvar</button>
            <a href="{{ route('clientes.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</a>
        </div>
    </form>
</div>
