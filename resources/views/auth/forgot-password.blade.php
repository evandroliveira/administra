<x-guest-layout>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="rounded-4 border border-white border-opacity-25 bg-white shadow-lg p-4 p-lg-5 auth-surface">
                <span class="badge rounded-pill text-bg-primary px-3 py-2">Recuperação de acesso</span>
                <h1 class="h2 fw-semibold mt-3">Esqueceu sua senha?</h1>
                <p class="text-body-secondary mt-3">Informe seu e-mail e enviaremos um link para redefinir a senha da sua conta.</p>

                @if (session('status'))
                    <div class="alert alert-success rounded-4 mt-4" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-4">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" class="form-control form-control-lg" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="voce@empresa.com">
                        @error('email')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-4">
                        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Voltar ao login</a>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Enviar link de redefinição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
