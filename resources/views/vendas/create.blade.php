<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova Venda</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('vendas.store') }}" id="form-venda" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Cliente</label>
                            <select name="cliente_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Selecione</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected((int) old('cliente_id') === (int) $cliente->id)>{{ $cliente->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'pendente') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vencimento (conta)</label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Desconto</label>
                            <input type="number" name="desconto" step="0.01" min="0" value="{{ old('desconto', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Frete</label>
                            <input type="number" name="frete" step="0.01" min="0" value="{{ old('frete', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Data de entrega</label>
                            <input type="date" name="data_entrega" value="{{ old('data_entrega') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Observações</label>
                            <textarea name="observacoes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observacoes') }}</textarea>
                        </div>
                    </div>

                    @if ($fiscalHabilitada)
                        <div class="border rounded-lg p-4 space-y-3 bg-slate-50/70">
                            <div>
                                <h3 class="font-semibold text-gray-800">Nota Fiscal</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    @if ($fiscalConfigurada)
                                        Integração ativa: após confirmar a venda, o sistema tenta emitir a nota automaticamente usando {{ $fiscalProviderLabel }}.
                                    @else
                                        Integração fiscal habilitada, mas ainda não configurada. A venda pode ficar com emissão pendente.
                                    @endif
                                </p>
                            </div>

                            <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700">
                                <input type="hidden" name="emitir_nota_fiscal" value="0">
                                <input type="checkbox" name="emitir_nota_fiscal" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('emitir_nota_fiscal'))>
                                Emitir nota fiscal desta venda
                            </label>
                        </div>
                    @endif

                    <div class="border rounded-lg p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Modalidade de pagamento</label>
                                <select id="modalidade_pagamento" name="modalidade_pagamento" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="conta" @selected(old('modalidade_pagamento', old('gerar_promissoria') ? 'promissoria' : 'conta') === 'conta')>Conta a receber</option>
                                    <option value="avista" @selected(old('modalidade_pagamento') === 'avista')>À vista</option>
                                    @if ($promissoriaHabilitada)
                                        <option value="promissoria" @selected(old('modalidade_pagamento', old('gerar_promissoria') ? 'promissoria' : 'conta') === 'promissoria')>Promissória</option>
                                    @endif
                                </select>
                            </div>
                            <div id="avista-config">
                                <label class="block text-sm font-medium text-gray-700">Método do pagamento à vista</label>
                                <select name="metodo_pagamento_avista" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    @foreach ($metodosPagamentoReceber as $valor => $label)
                                        <option value="{{ $valor }}" @selected(old('metodo_pagamento_avista', 'dinheiro') === $valor)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="gerar_promissoria" value="{{ old('gerar_promissoria') ? 1 : 0 }}">

                        @if ($promissoriaHabilitada)
                            <div id="promissoria-config" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Entrada</label>
                                    <input type="number" name="valor_entrada" step="0.01" min="0" value="{{ old('valor_entrada', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantidade de parcelas</label>
                                    <input type="number" name="quantidade_parcelas" min="1" value="{{ old('quantidade_parcelas', 1) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Intervalo entre parcelas (dias)</label>
                                    <input type="number" name="intervalo_dias" min="1" value="{{ old('intervalo_dias', 30) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Primeira parcela</label>
                                    <input type="date" name="data_primeira_parcela" value="{{ old('data_primeira_parcela', now()->addDays(30)->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Multa (%)</label>
                                    <input type="number" name="percentual_multa_atraso" step="0.01" min="0" value="{{ old('percentual_multa_atraso') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Juros ao dia (%)</label>
                                    <input type="number" name="percentual_juros_dia" step="0.0001" min="0" value="{{ old('percentual_juros_dia') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Observações da promissória</label>
                                    <input type="text" name="observacoes_promissoria" value="{{ old('observacoes_promissoria') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Seu plano atual não permite operar promissórias.
                            </div>
                        @endif
                    </div>

                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-800">Itens da venda</h3>
                            <button type="button" id="adicionar-item" class="px-3 py-2 bg-gray-700 text-white rounded-md text-sm">Adicionar item</button>
                        </div>
                        <div id="itens-container" class="space-y-3"></div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Salvar venda</button>
                        <a href="{{ route('vendas.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $produtosPayload = $produtos->map(function ($produto) {
            return [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'preco_venda' => (float) $produto->preco_venda,
                'estoque_atual' => (int) $produto->estoque_atual,
            ];
        })->values()->all();
        $itensPayload = old('itens', [['produto_id' => '', 'quantidade' => 1, 'preco_unitario' => '']]);
    @endphp

    <script>
        const produtos = {{ \Illuminate\Support\Js::from($produtosPayload) }};
        const itensOld = {{ \Illuminate\Support\Js::from($itensPayload) }};
        const container = document.getElementById('itens-container');
        const addBtn = document.getElementById('adicionar-item');
        const modalidadePagamentoSelect = document.getElementById('modalidade_pagamento');
        const gerarPromissoriaInput = document.querySelector('input[name="gerar_promissoria"]');
        const avistaConfig = document.getElementById('avista-config');
        const promissoriaConfig = document.getElementById('promissoria-config');
        const promissoriaHabilitada = {{ $promissoriaHabilitada ? 'true' : 'false' }};

        function toggleModalidadePagamento() {
            const modalidade = modalidadePagamentoSelect.value;
            const promissoriaEnabled = promissoriaHabilitada && modalidade === 'promissoria';
            const avistaEnabled = modalidade === 'avista';

            gerarPromissoriaInput.value = promissoriaEnabled ? '1' : '0';

            if (promissoriaConfig) {
                promissoriaConfig.classList.toggle('opacity-50', !promissoriaEnabled);
                promissoriaConfig.querySelectorAll('input').forEach((input) => {
                    input.disabled = !promissoriaEnabled;
                });
            }

            avistaConfig.classList.toggle('opacity-50', !avistaEnabled);
            avistaConfig.querySelectorAll('select').forEach((input) => {
                input.disabled = !avistaEnabled;
            });
        }

        function optionProdutos(selectedId) {
            let html = '<option value="">Selecione</option>';
            for (const produto of produtos) {
                const selected = String(selectedId) === String(produto.id) ? 'selected' : '';
                html += `<option value="${produto.id}" ${selected}>${produto.nome} (Estoque: ${produto.estoque_atual})</option>`;
            }
            return html;
        }

        function addItem(data = {produto_id: '', quantidade: 1, preco_unitario: ''}) {
            const idx = container.children.length;
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-4 gap-3 p-3 border rounded';
            row.innerHTML = `
                <div>
                    <label class="block text-xs font-medium text-gray-600">Produto</label>
                    <select name="itens[${idx}][produto_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">${optionProdutos(data.produto_id)}</select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Quantidade</label>
                    <input type="number" min="1" name="itens[${idx}][quantidade]" value="${data.quantidade ?? 1}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Preço unitário</label>
                    <input type="number" min="0.01" step="0.01" name="itens[${idx}][preco_unitario]" value="${data.preco_unitario ?? ''}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div class="flex items-end">
                    <button type="button" class="remover-item px-3 py-2 bg-red-600 text-white rounded-md text-sm w-full">Remover</button>
                </div>
            `;

            row.querySelector('.remover-item').addEventListener('click', () => {
                row.remove();
                reindex();
            });

            row.querySelector(`select[name="itens[${idx}][produto_id]"]`).addEventListener('change', (ev) => {
                const produto = produtos.find((p) => String(p.id) === ev.target.value);
                const priceInput = row.querySelector(`input[name="itens[${idx}][preco_unitario]"]`);
                if (produto && !priceInput.value) {
                    priceInput.value = produto.preco_venda.toFixed(2);
                }
            });

            container.appendChild(row);
        }

        function reindex() {
            [...container.children].forEach((row, idx) => {
                row.querySelectorAll('select, input').forEach((el) => {
                    el.name = el.name.replace(/itens\[\d+\]/, `itens[${idx}]`);
                });
            });
        }

        addBtn.addEventListener('click', () => addItem());
        modalidadePagamentoSelect.addEventListener('change', toggleModalidadePagamento);
        itensOld.forEach((item) => addItem(item));
        toggleModalidadePagamento();
    </script>
</x-app-layout>
