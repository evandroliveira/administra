<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4 p-lg-5">
        @if ($errors->any())
            <div class="alert alert-danger rounded-4 mb-4" role="alert">
                <div class="fw-semibold mb-2">Revise os campos abaixo.</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="row g-3" data-cep-form>
            @csrf
            @if ($method !== 'POST') @method($method) @endif

            <div class="col-md-6">
                <label for="tipo" class="form-label fw-semibold">Tipo</label>
                <select id="tipo" name="tipo" class="form-select form-select-lg">
                    <option value="PF" @selected(old('tipo', $cliente->tipo ?? 'PF') === 'PF')>PF</option>
                    <option value="PJ" @selected(old('tipo', $cliente->tipo ?? 'PF') === 'PJ')>PJ</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="nome" class="form-label fw-semibold">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome', $cliente->nome ?? '') }}" class="form-control form-control-lg" required>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $cliente->email ?? '') }}" class="form-control form-control-lg" required>
            </div>

            <div class="col-md-6">
                <label for="telefone" class="form-label fw-semibold">Telefone</label>
                <input id="telefone" name="telefone" value="{{ old('telefone', $cliente->telefone ?? '') }}" class="form-control form-control-lg" required>
            </div>

            <div class="col-md-6">
                <label for="celular" class="form-label fw-semibold">Celular</label>
                <input id="celular" name="celular" value="{{ old('celular', $cliente->celular ?? '') }}" class="form-control form-control-lg">
            </div>

            <div class="col-md-6">
                <label for="cpf_cnpj" class="form-label fw-semibold">CPF/CNPJ</label>
                <input id="cpf_cnpj" name="cpf_cnpj" value="{{ old('cpf_cnpj', $cliente->cpf_cnpj ?? '') }}" class="form-control form-control-lg" required>
            </div>

            <div class="col-md-6">
                <label for="rg_ie" class="form-label fw-semibold">RG/IE</label>
                <input id="rg_ie" name="rg_ie" value="{{ old('rg_ie', $cliente->rg_ie ?? '') }}" class="form-control form-control-lg">
            </div>

            <div class="col-md-4">
                <label for="cep" class="form-label fw-semibold">CEP</label>
                <input id="cep" name="cep" value="{{ old('cep', $cliente->cep ?? '') }}" class="form-control form-control-lg" inputmode="numeric" maxlength="9" placeholder="00000-000" data-cep-input required>
                <div class="form-text" data-cep-status></div>
            </div>

            <div class="col-md-8">
                <label for="endereco" class="form-label fw-semibold">Endereco</label>
                <input id="endereco" name="endereco" value="{{ old('endereco', $cliente->endereco ?? '') }}" class="form-control form-control-lg" data-cep-endereco required>
            </div>

            <div class="col-md-4">
                <label for="numero" class="form-label fw-semibold">Numero</label>
                <input id="numero" name="numero" value="{{ old('numero', $cliente->numero ?? '') }}" class="form-control form-control-lg" required>
            </div>

            <div class="col-md-8">
                <label for="complemento" class="form-label fw-semibold">Complemento</label>
                <input id="complemento" name="complemento" value="{{ old('complemento', $cliente->complemento ?? '') }}" class="form-control form-control-lg">
            </div>

            <div class="col-md-4">
                <label for="bairro" class="form-label fw-semibold">Bairro</label>
                <input id="bairro" name="bairro" value="{{ old('bairro', $cliente->bairro ?? '') }}" class="form-control form-control-lg" data-cep-bairro required>
            </div>

            <div class="col-md-4">
                <label for="cidade" class="form-label fw-semibold">Cidade</label>
                <input id="cidade" name="cidade" value="{{ old('cidade', $cliente->cidade ?? '') }}" class="form-control form-control-lg" data-cep-cidade required>
            </div>

            <div class="col-md-2">
                <label for="estado" class="form-label fw-semibold">Estado</label>
                <input id="estado" name="estado" maxlength="2" value="{{ old('estado', $cliente->estado ?? '') }}" class="form-control form-control-lg text-uppercase" data-cep-estado required>
            </div>

            <div class="col-md-4">
                <label for="limite_credito" class="form-label fw-semibold">Limite de credito</label>
                <input id="limite_credito" name="limite_credito" type="number" step="0.01" min="0" value="{{ old('limite_credito', $cliente->limite_credito ?? 0) }}" class="form-control form-control-lg">
            </div>

            <div class="col-md-4">
                <label for="percentual_multa_atraso_padrao" class="form-label fw-semibold">Multa atraso (%)</label>
                <input id="percentual_multa_atraso_padrao" name="percentual_multa_atraso_padrao" type="number" step="0.01" min="0" value="{{ old('percentual_multa_atraso_padrao', $cliente->percentual_multa_atraso_padrao ?? 2) }}" class="form-control form-control-lg">
            </div>

            <div class="col-md-4">
                <label for="percentual_juros_dia_padrao" class="form-label fw-semibold">Juros dia (%)</label>
                <input id="percentual_juros_dia_padrao" name="percentual_juros_dia_padrao" type="number" step="0.0001" min="0" value="{{ old('percentual_juros_dia_padrao', $cliente->percentual_juros_dia_padrao ?? 0.0333) }}" class="form-control form-control-lg">
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $cliente->ativo ?? true))>
                    <label class="form-check-label fw-semibold" for="ativo">Ativo</label>
                </div>
            </div>

            <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-3 border-top mt-3">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar</button>
                <a href="{{ route('clientes.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
