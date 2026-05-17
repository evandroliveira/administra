<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4 p-lg-5">
        @if ($errors->any())
            <div class="alert alert-danger rounded-4 mb-4" role="alert">
                <div class="fw-semibold mb-2">Revise os campos abaixo.</div>
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="row g-3">
            @csrf
            @if ($method !== 'POST') @method($method) @endif
            <div class="col-md-4"><label for="codigo" class="form-label fw-semibold">Codigo</label><input id="codigo" name="codigo" value="{{ old('codigo', $produto->codigo ?? '') }}" class="form-control form-control-lg" required></div>
            <div class="col-md-8"><label for="nome" class="form-label fw-semibold">Nome</label><input id="nome" name="nome" value="{{ old('nome', $produto->nome ?? '') }}" class="form-control form-control-lg" required></div>
            <div class="col-12"><label for="descricao" class="form-label fw-semibold">Descricao</label><textarea id="descricao" name="descricao" rows="3" class="form-control form-control-lg">{{ old('descricao', $produto->descricao ?? '') }}</textarea></div>
            <div class="col-md-6">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-2">
                    <label for="categoria_id" class="form-label fw-semibold mb-0">Categoria</label>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalNovaCategoria">
                        Nova categoria
                    </button>
                </div>
                <select id="categoria_id" name="categoria_id" class="form-select form-select-lg" data-categoria-select>
                    <option value="">Sem categoria</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $produto->categoria_id ?? 0) === (int) $categoria->id)>{{ $categoria->nome }}{{ $categoria->ativo ? '' : ' (inativa)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label for="preco_custo" class="form-label fw-semibold">Preco custo</label><input id="preco_custo" type="number" step="0.01" min="0.01" name="preco_custo" value="{{ old('preco_custo', $produto->preco_custo ?? 0) }}" class="form-control form-control-lg" required></div>
            <div class="col-md-3"><label for="preco_venda" class="form-label fw-semibold">Preco venda</label><input id="preco_venda" type="number" step="0.01" min="0.01" name="preco_venda" value="{{ old('preco_venda', $produto->preco_venda ?? 0) }}" class="form-control form-control-lg" required></div>
            <div class="col-md-4"><label for="custo_medio" class="form-label fw-semibold">Custo medio</label><input id="custo_medio" type="number" step="0.01" min="0" name="custo_medio" value="{{ old('custo_medio', $produto->custo_medio ?? 0) }}" class="form-control form-control-lg"></div>
            <div class="col-md-4"><label for="estoque_atual" class="form-label fw-semibold">Estoque atual</label><input id="estoque_atual" type="number" min="0" name="estoque_atual" value="{{ old('estoque_atual', $produto->estoque_atual ?? 0) }}" class="form-control form-control-lg"></div>
            <div class="col-md-4"><label for="estoque_minimo" class="form-label fw-semibold">Estoque minimo</label><input id="estoque_minimo" type="number" min="0" name="estoque_minimo" value="{{ old('estoque_minimo', $produto->estoque_minimo ?? 10) }}" class="form-control form-control-lg"></div>
            <div class="col-12"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $produto->ativo ?? true))><label class="form-check-label fw-semibold" for="ativo">Ativo</label></div></div>
            <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-3 border-top mt-3"><button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar</button><a href="{{ route('produtos.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancelar</a></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalNovaCategoria" tabindex="-1" aria-labelledby="modalNovaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
                    <h3 class="h4 fw-semibold mb-1" id="modalNovaCategoriaLabel">Nova categoria</h3>
                    <p class="text-body-secondary mb-0">Cadastre a categoria sem sair do produto.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{ route('categorias.store') }}" method="POST" data-categoria-modal-form>
                    @csrf
                    <div class="alert alert-danger rounded-4 d-none" data-categoria-feedback role="alert"></div>

                    <div class="mb-3">
                        <label for="categoria_modal_nome" class="form-label fw-semibold">Nome</label>
                        <input id="categoria_modal_nome" name="nome" class="form-control form-control-lg" maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="categoria_modal_descricao" class="form-label fw-semibold">Descricao</label>
                        <textarea id="categoria_modal_descricao" name="descricao" rows="3" class="form-control"></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="categoria_modal_ativo" name="ativo" value="1" checked>
                        <label class="form-check-label fw-semibold" for="categoria_modal_ativo">Categoria ativa</label>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4" data-categoria-submit>Salvar categoria</button>
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('modalNovaCategoria');
    const form = document.querySelector('[data-categoria-modal-form]');
    const select = document.querySelector('[data-categoria-select]');

    if (!modalElement || !form || !select || !window.bootstrap) {
        return;
    }

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    const feedback = form.querySelector('[data-categoria-feedback]');
    const submitButton = form.querySelector('[data-categoria-submit]');
    const nomeInput = form.querySelector('#categoria_modal_nome');
    const descricaoInput = form.querySelector('#categoria_modal_descricao');
    const ativoInput = form.querySelector('#categoria_modal_ativo');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const submitLabel = submitButton.textContent;

    const limparErros = function () {
        feedback.classList.add('d-none');
        feedback.innerHTML = '';
        [nomeInput, descricaoInput].forEach(function (input) {
            input.classList.remove('is-invalid');
        });
    };

    modalElement.addEventListener('hidden.bs.modal', function () {
        form.reset();
        limparErros();

        if (ativoInput) {
            ativoInput.checked = true;
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        limparErros();

        submitButton.disabled = true;
        submitButton.textContent = 'Salvando...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                const errors = payload.errors || {};
                const messages = Object.values(errors).flat();

                if (errors.nome) {
                    nomeInput.classList.add('is-invalid');
                }

                if (errors.descricao) {
                    descricaoInput.classList.add('is-invalid');
                }

                feedback.innerHTML = (messages.length ? messages : [payload.message || 'Nao foi possivel cadastrar a categoria.'])
                    .map(function (message) {
                        return '<div>' + message + '</div>';
                    })
                    .join('');
                feedback.classList.remove('d-none');

                return;
            }

            const categoria = payload.categoria || {};
            let option = Array.from(select.options).find(function (item) {
                return item.value === String(categoria.id);
            });

            if (!option) {
                option = new Option(categoria.nome, categoria.id, true, true);
                select.add(option);
            } else {
                option.text = categoria.nome;
                option.selected = true;
            }

            select.value = String(categoria.id);
            select.dispatchEvent(new Event('change', { bubbles: true }));
            modal.hide();
        } catch (error) {
            feedback.innerHTML = '<div>Nao foi possivel cadastrar a categoria agora.</div>';
            feedback.classList.remove('d-none');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = submitLabel;
        }
    });
});
</script>
