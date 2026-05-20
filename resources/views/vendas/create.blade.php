<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Comercial</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Nova Venda</h2>
            <p class="text-body-secondary mb-0">Monte a venda, escolha a modalidade de pagamento e acompanhe a emissão fiscal quando aplicável.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 mb-0">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <form method="POST" action="{{ route('vendas.store') }}" id="form-venda" class="d-flex flex-column gap-4">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <label for="cliente_busca" class="form-label fw-semibold">Buscar cliente pelo nome</label>
                                    <input id="cliente_busca" type="search" name="cliente_busca" value="{{ old('cliente_busca') }}" class="form-control form-control-lg" placeholder="Digite para filtrar clientes" autocomplete="off">
                                    <div class="form-text">Digite parte do nome para reduzir a lista de clientes abaixo.</div>
                                </div>
                                <div>
                                    <label for="cliente_id" class="form-label fw-semibold">Cliente</label>
                                    <select id="cliente_id" name="cliente_id" required class="form-select form-select-lg">
                                        <option value="">Selecione</option>
                                        @foreach ($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" @selected((int) old('cliente_id') === (int) $cliente->id)>{{ $cliente->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status" class="form-select form-select-lg">
                                @foreach (['pendente', 'confirmada', 'concluida', 'cancelada'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'pendente') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="data_vencimento" class="form-label fw-semibold">Vencimento (conta/boleto)</label>
                            <input id="data_vencimento" type="date" name="data_vencimento" value="{{ old('data_vencimento') }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-md-3">
                            <label for="desconto" class="form-label fw-semibold">Desconto</label>
                            <input id="desconto" type="number" name="desconto" step="0.01" min="0" value="{{ old('desconto', 0) }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-md-3">
                            <label for="frete" class="form-label fw-semibold">Frete</label>
                            <input id="frete" type="number" name="frete" step="0.01" min="0" value="{{ old('frete', 0) }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-md-6">
                            <label for="data_entrega" class="form-label fw-semibold">Data de entrega</label>
                            <input id="data_entrega" type="date" name="data_entrega" value="{{ old('data_entrega') }}" class="form-control form-control-lg">
                        </div>
                        <div class="col-12">
                            <label for="observacoes" class="form-label fw-semibold">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="2" class="form-control form-control-lg">{{ old('observacoes') }}</textarea>
                        </div>
                    </div>

                    @if ($fiscalHabilitada)
                        <div class="border rounded-4 p-4 bg-light-subtle">
                            <div>
                                <h3 class="h5 fw-semibold text-dark mb-2">Nota Fiscal</h3>
                                <p class="text-body-secondary mb-0">
                                    @if ($fiscalConfigurada)
                                        Integração ativa: após confirmar a venda, o sistema tenta emitir a nota automaticamente usando {{ $fiscalProviderLabel }}.
                                    @else
                                        Integração fiscal habilitada, mas ainda não configurada. A venda pode ficar com emissão pendente.
                                    @endif
                                </p>
                            </div>

                            <label class="form-check d-flex align-items-center gap-2 mt-3 mb-0">
                                <input type="hidden" name="emitir_nota_fiscal" value="0">
                                <input type="checkbox" name="emitir_nota_fiscal" value="1" class="form-check-input" @checked(old('emitir_nota_fiscal'))>
                                <span class="form-check-label fw-semibold text-dark">Emitir nota fiscal desta venda</span>
                            </label>
                        </div>
                    @endif

                    <div class="border rounded-4 p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="modalidade_pagamento" class="form-label fw-semibold">Modalidade de pagamento</label>
                                <select id="modalidade_pagamento" name="modalidade_pagamento" class="form-select form-select-lg">
                                    <option value="conta" @selected(old('modalidade_pagamento', old('gerar_promissoria') ? 'promissoria' : 'conta') === 'conta')>Conta a receber</option>
                                    <option value="boleto" @selected(old('modalidade_pagamento') === 'boleto')>Boleto bancário</option>
                                    <option value="avista" @selected(old('modalidade_pagamento') === 'avista')>À vista</option>
                                    @if ($promissoriaHabilitada)
                                        <option value="promissoria" @selected(old('modalidade_pagamento', old('gerar_promissoria') ? 'promissoria' : 'conta') === 'promissoria')>Promissória</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6" id="avista-config">
                                <label for="metodo_pagamento_avista" class="form-label fw-semibold">Método do pagamento à vista</label>
                                <select id="metodo_pagamento_avista" name="metodo_pagamento_avista" class="form-select form-select-lg">
                                    @foreach ($metodosPagamentoReceber as $valor => $label)
                                        <option value="{{ $valor }}" @selected(old('metodo_pagamento_avista', 'dinheiro') === $valor)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="gerar_promissoria" value="{{ old('gerar_promissoria') ? 1 : 0 }}">

                        @if ($promissoriaHabilitada)
                            <div id="promissoria-config" class="row g-3 mt-1">
                                <div class="col-md-3">
                                    <label for="valor_entrada" class="form-label fw-semibold">Entrada</label>
                                    <input id="valor_entrada" type="number" name="valor_entrada" step="0.01" min="0" value="{{ old('valor_entrada', 0) }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-3">
                                    <label for="quantidade_parcelas" class="form-label fw-semibold">Quantidade de parcelas</label>
                                    <input id="quantidade_parcelas" type="number" name="quantidade_parcelas" min="1" value="{{ old('quantidade_parcelas', 1) }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-3">
                                    <label for="intervalo_dias" class="form-label fw-semibold">Intervalo entre parcelas (dias)</label>
                                    <input id="intervalo_dias" type="number" name="intervalo_dias" min="1" value="{{ old('intervalo_dias', 30) }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-3">
                                    <label for="data_primeira_parcela" class="form-label fw-semibold">Primeira parcela</label>
                                    <input id="data_primeira_parcela" type="date" name="data_primeira_parcela" value="{{ old('data_primeira_parcela', now()->addDays(30)->toDateString()) }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-3">
                                    <label for="percentual_multa_atraso" class="form-label fw-semibold">Multa (%)</label>
                                    <input id="percentual_multa_atraso" type="number" name="percentual_multa_atraso" step="0.01" min="0" value="{{ old('percentual_multa_atraso') }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-3">
                                    <label for="percentual_juros_dia" class="form-label fw-semibold">Juros ao dia (%)</label>
                                    <input id="percentual_juros_dia" type="number" name="percentual_juros_dia" step="0.0001" min="0" value="{{ old('percentual_juros_dia') }}" class="form-control form-control-lg">
                                </div>
                                <div class="col-md-6">
                                    <label for="observacoes_promissoria" class="form-label fw-semibold">Observações da promissória</label>
                                    <input id="observacoes_promissoria" type="text" name="observacoes_promissoria" value="{{ old('observacoes_promissoria') }}" class="form-control form-control-lg">
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning rounded-4 mt-3 mb-0">
                                Seu plano atual não permite operar promissórias.
                            </div>
                        @endif
                    </div>

                    <div class="border rounded-4 p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
                            <div class="d-flex flex-column gap-3 flex-grow-1">
                                <h3 class="h5 fw-semibold text-dark mb-0">Itens da venda</h3>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6 col-lg-5">
                                        <label for="categoria_filtro_produto" class="form-label fw-semibold">Filtrar produtos por categoria</label>
                                        <select id="categoria_filtro_produto" class="form-select form-select-lg">
                                            <option value="">Todas as categorias</option>
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->id }}">{{ $categoria->nome }}{{ $categoria->ativo ? '' : ' (inativa)' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="adicionar-item" class="btn btn-outline-dark rounded-pill">Adicionar item</button>
                        </div>
                        <div id="itens-container"></div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar venda</button>
                        <a href="{{ route('vendas.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancelar</a>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $clientesPayload = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
            ];
        })->values()->all();
        $produtosPayload = $produtos->map(function ($produto) {
            return [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'categoria_id' => $produto->categoria_id,
                'preco_venda' => (float) $produto->preco_venda,
                'estoque_atual' => (int) $produto->estoque_atual,
            ];
        })->values()->all();
        $itensPayload = old('itens', [['produto_id' => '', 'produto_busca' => '', 'quantidade' => 1, 'preco_unitario' => '']]);
    @endphp

    <script>
        const clientes = {{ \Illuminate\Support\Js::from($clientesPayload) }};
        const produtos = {{ \Illuminate\Support\Js::from($produtosPayload) }};
        const itensOld = {{ \Illuminate\Support\Js::from($itensPayload) }};
        const clienteBuscaInput = document.getElementById('cliente_busca');
        const clienteSelect = document.getElementById('cliente_id');
        const container = document.getElementById('itens-container');
        const addBtn = document.getElementById('adicionar-item');
        const categoriaFiltroSelect = document.getElementById('categoria_filtro_produto');
        const modalidadePagamentoSelect = document.getElementById('modalidade_pagamento');
        const gerarPromissoriaInput = document.querySelector('input[name="gerar_promissoria"]');
        const avistaConfig = document.getElementById('avista-config');
        const promissoriaConfig = document.getElementById('promissoria-config');
        const promissoriaHabilitada = {{ $promissoriaHabilitada ? 'true' : 'false' }};

        function normalizeText(value) {
            const text = String(value ?? '');
            const normalized = typeof text.normalize === 'function'
                ? text.normalize('NFD')
                : text;

            return normalized
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function uniqueById(items) {
            const seen = new Set();

            return items.filter((item) => {
                const key = String(item.id);

                if (seen.has(key)) {
                    return false;
                }

                seen.add(key);

                return true;
            });
        }

        function clientePorId(clienteId) {
            return clientes.find((cliente) => String(cliente.id) === String(clienteId)) ?? null;
        }

        function produtoPorId(produtoId) {
            return produtos.find((produto) => String(produto.id) === String(produtoId)) ?? null;
        }

        function clienteLabel(cliente) {
            return cliente.nome;
        }

        function produtoLabel(produto) {
            return `${produto.nome} (Estoque: ${produto.estoque_atual})`;
        }

        function optionTags(items, selectedId, placeholder, labelCallback) {
            let html = `<option value="">${placeholder}</option>`;

            for (const item of items) {
                const selected = String(selectedId) === String(item.id) ? 'selected' : '';
                html += `<option value="${item.id}" ${selected}>${labelCallback(item)}</option>`;
            }

            return html;
        }

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

        function syncClienteOptions(preserveSelected = true, autoSelectFirst = false) {
            if (!clienteSelect || !clienteBuscaInput) {
                return;
            }

            const searchTerm = normalizeText(clienteBuscaInput.value);
            const selectedId = clienteSelect.value;
            const clienteSelecionado = clientePorId(selectedId);
            const clientesFiltrados = clientes.filter((cliente) => searchTerm === '' || normalizeText(cliente.nome).includes(searchTerm));
            const manterSelecionado = preserveSelected
                && clienteSelecionado
                && !clientesFiltrados.some((cliente) => String(cliente.id) === String(selectedId));
            const opcoes = manterSelecionado
                ? uniqueById([clienteSelecionado, ...clientesFiltrados])
                : clientesFiltrados;

            clienteSelect.innerHTML = optionTags(opcoes, selectedId, 'Selecione', clienteLabel);

            if (searchTerm !== '' && autoSelectFirst && opcoes.length > 0) {
                clienteSelect.value = String(opcoes[0].id);
                return;
            }

            if (selectedId !== '' && !opcoes.some((cliente) => String(cliente.id) === String(selectedId))) {
                clienteSelect.value = '';
            }
        }

        function optionProdutos(selectedId, searchTerm = '', preserveSelected = true) {
            const categoriaId = categoriaFiltroSelect ? String(categoriaFiltroSelect.value) : '';
            const normalizedSearchTerm = normalizeText(searchTerm);
            const produtoSelecionado = produtoPorId(selectedId);
            const produtosFiltrados = produtos.filter((produto) => {
                const categoriaCompativel = categoriaId === '' || String(produto.categoria_id ?? '') === categoriaId;
                const buscaCompativel = normalizedSearchTerm === '' || normalizeText(produto.nome).includes(normalizedSearchTerm);

                return categoriaCompativel && buscaCompativel;
            });
            const produtoSelecionadoCompativel = produtoSelecionado
                && (categoriaId === '' || String(produtoSelecionado.categoria_id ?? '') === categoriaId);
            const manterSelecionado = preserveSelected
                && produtoSelecionadoCompativel
                && !produtosFiltrados.some((produto) => String(produto.id) === String(selectedId));
            const opcoes = manterSelecionado
                ? uniqueById([produtoSelecionado, ...produtosFiltrados])
                : produtosFiltrados;

            return optionTags(opcoes, selectedId, 'Selecione', produtoLabel);
        }

        function syncRowProdutoOptions(row, preserveSelected = true, autoSelectFirst = false) {
            const searchInput = row.querySelector('.item-produto-search');
            const select = row.querySelector('.item-produto-select');

            if (!searchInput || !select) {
                return;
            }

            const selectedId = select.value;
            const searchTerm = normalizeText(searchInput.value);
            select.innerHTML = optionProdutos(selectedId, searchInput.value, preserveSelected);

            if (searchTerm !== '' && autoSelectFirst) {
                const primeiraOpcaoValida = [...select.options].find((option) => option.value !== '');

                if (primeiraOpcaoValida) {
                    select.value = primeiraOpcaoValida.value;

                    const produtoSelecionado = produtoPorId(primeiraOpcaoValida.value);
                    const priceInput = row.querySelector('.item-preco-unitario');

                    if (produtoSelecionado && priceInput && !priceInput.value) {
                        priceInput.value = produtoSelecionado.preco_venda.toFixed(2);
                    }

                    return;
                }
            }

            const existeOpcao = [...select.options].some((option) => option.value === String(selectedId));

            if (!existeOpcao && selectedId !== '') {
                select.value = '';

                const priceInput = row.querySelector('.item-preco-unitario');
                if (priceInput) {
                    priceInput.value = '';
                }
            }
        }

        function syncProdutoOptions() {
            container.querySelectorAll('.row').forEach((row) => {
                syncRowProdutoOptions(row, false);
            });
        }

        function addItem(data = {produto_id: '', produto_busca: '', quantidade: 1, preco_unitario: ''}) {
            const idx = container.children.length;
            const produtoSelecionado = produtoPorId(data.produto_id);
            const buscaInicial = data.produto_busca ?? produtoSelecionado?.nome ?? '';
            const row = document.createElement('div');
            row.className = 'row g-3 p-3 border rounded-4 bg-light-subtle mb-3';
            row.innerHTML = `
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Buscar produto pelo nome</label>
                    <input type="search" name="itens[${idx}][produto_busca]" value="${escapeHtml(buscaInicial)}" class="form-control mb-2 item-produto-search" placeholder="Digite para filtrar produtos" autocomplete="off">
                    <label class="form-label fw-semibold small">Produto</label>
                    <select name="itens[${idx}][produto_id]" class="form-select item-produto-select"></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Quantidade</label>
                    <input type="number" min="1" name="itens[${idx}][quantidade]" value="${data.quantidade ?? 1}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Preço unitário</label>
                    <input type="number" min="0.01" step="0.01" name="itens[${idx}][preco_unitario]" value="${data.preco_unitario ?? ''}" class="form-control item-preco-unitario">
                </div>
                <div class="col-md-2 d-grid align-items-end">
                    <button type="button" class="remover-item btn btn-outline-danger">Remover</button>
                </div>
            `;

            row.querySelector('.remover-item').addEventListener('click', () => {
                row.remove();
                reindex();
            });

            row.querySelector('.item-produto-search').addEventListener('input', () => {
                syncRowProdutoOptions(row, false, true);
            });

            row.querySelector('.item-produto-select').addEventListener('change', (ev) => {
                const produto = produtos.find((p) => String(p.id) === ev.target.value);
                const searchInput = row.querySelector('.item-produto-search');
                const priceInput = row.querySelector('.item-preco-unitario');
                if (produto && !priceInput.value) {
                    priceInput.value = produto.preco_venda.toFixed(2);
                }

                if (searchInput) {
                    searchInput.value = produto ? produto.nome : '';
                }
            });

            container.appendChild(row);
            syncRowProdutoOptions(row);
        }

        function reindex() {
            [...container.children].forEach((row, idx) => {
                row.querySelectorAll('select, input').forEach((el) => {
                    el.name = el.name.replace(/itens\[\d+\]/, `itens[${idx}]`);
                });
            });
        }

        addBtn.addEventListener('click', () => addItem());
        if (categoriaFiltroSelect) {
            categoriaFiltroSelect.addEventListener('change', syncProdutoOptions);
        }
        if (clienteBuscaInput && clienteSelect) {
            clienteBuscaInput.addEventListener('input', () => syncClienteOptions(false, true));
            clienteSelect.addEventListener('change', () => {
                const cliente = clientePorId(clienteSelect.value);

                if (cliente) {
                    clienteBuscaInput.value = cliente.nome;
                }
            });

            syncClienteOptions();

            if (!clienteBuscaInput.value) {
                const clienteSelecionado = clientePorId(clienteSelect.value);
                if (clienteSelecionado) {
                    clienteBuscaInput.value = clienteSelecionado.nome;
                }
            }
        }
        modalidadePagamentoSelect.addEventListener('change', toggleModalidadePagamento);
        itensOld.forEach((item) => addItem(item));
        toggleModalidadePagamento();
    </script>
</x-app-layout>
