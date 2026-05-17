<x-guest-layout>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7 col-xl-6">
            <div class="rounded-4 border border-white border-opacity-25 bg-white shadow-lg p-4 p-lg-5 auth-surface">
                <span class="badge rounded-pill text-bg-primary px-3 py-2">Verificação</span>
                <h1 class="h2 fw-semibold mt-3">Verifique seu e-mail</h1>
                <p class="text-body-secondary mt-3 mb-0">Antes de começar, confirme seu endereço clicando no link enviado para sua caixa de entrada. Se necessário, podemos reenviar agora.</p>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success rounded-4 mt-4 mb-0" role="alert">
                        Um novo link de verificação foi enviado para o e-mail informado no cadastro.
                    </div>
                @endif

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Reenviar e-mail de verificação</button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-lg rounded-pill px-4">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
