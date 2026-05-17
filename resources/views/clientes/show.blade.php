<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Cadastros</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Cliente</h2>
                <p class="text-body-secondary mb-0">Consulte os dados cadastrais e o saldo de credito deste cliente.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning btn-lg rounded-pill px-4">Editar</a>
                <a href="{{ route('clientes.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Voltar</a>
            </div>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-3 small">
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Nome</div><div class="text-dark">{{ $cliente->nome }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Tipo</div><div class="text-dark">{{ $cliente->tipo }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Documento</div><div class="text-dark">{{ $cliente->cpf_cnpj }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Telefone</div><div class="text-dark">{{ $cliente->telefone }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">E-mail</div><div class="text-dark">{{ $cliente->email }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Cidade/UF</div><div class="text-dark">{{ $cliente->cidade }}/{{ $cliente->estado }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Limite</div><div class="text-dark">R$ {{ number_format((float) $cliente->limite_credito, 2, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Credito disponivel</div><div class="text-dark">R$ {{ number_format((float) $cliente->credito_disponivel, 2, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Ativo</div><div class="text-dark">{{ $cliente->ativo ? 'Sim' : 'Não' }}</div></div></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
