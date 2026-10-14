<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Financeiro</span>
            <h2 class="h1 fw-semibold text-dark mb-2">Nova Conta a Pagar</h2>
            <p class="text-body-secondary mb-0">Cadastre despesas recorrentes ou pontuais com fornecedor, vencimento e juros previstos.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="mx-auto" style="max-width: 980px;">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <form method="POST" action="{{ route('contas.pagar.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label for="descricao" class="form-label fw-semibold">Descrição</label>
                        <input id="descricao" type="text" name="descricao" required maxlength="200" value="{{ old('descricao') }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6">
                        <label for="fornecedor" class="form-label fw-semibold">Fornecedor</label>
                        <input id="fornecedor" type="text" name="fornecedor" required maxlength="200" value="{{ old('fornecedor') }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6">
                        <label for="data_vencimento" class="form-label fw-semibold">Vencimento</label>
                        <input id="data_vencimento" type="date" name="data_vencimento" required value="{{ old('data_vencimento') }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6">
                        <label for="valor_original" class="form-label fw-semibold">Valor original</label>
                        <input id="valor_original" type="number" step="0.01" min="0.01" name="valor_original" required value="{{ old('valor_original') }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6">
                        <label for="valor_juros" class="form-label fw-semibold">Juros</label>
                        <input id="valor_juros" type="number" step="0.01" min="0" name="valor_juros" value="{{ old('valor_juros', 0) }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-12">
                        <label for="observacoes" class="form-label fw-semibold">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3" class="form-control form-control-lg">{{ old('observacoes') }}</textarea>
                    </div>
                    <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-3 border-top mt-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar</button>
                        <a href="{{ route('contas.pagar.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancelar</a>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
