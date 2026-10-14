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

            <div class="col-md-8">
                <label for="nome" class="form-label fw-semibold">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome', $categoria->nome ?? '') }}" class="form-control form-control-lg" maxlength="100" required>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $categoria->ativo ?? true))>
                    <label class="form-check-label fw-semibold" for="ativo">Ativa</label>
                </div>
            </div>

            <div class="col-12">
                <label for="descricao" class="form-label fw-semibold">Descricao</label>
                <textarea id="descricao" name="descricao" rows="4" class="form-control form-control-lg">{{ old('descricao', $categoria->descricao ?? '') }}</textarea>
            </div>

            <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-3 border-top mt-3">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar</button>
                <a href="{{ route('categorias.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>