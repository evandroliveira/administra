<section>
    <header>
        <h2 class="h4 fw-semibold text-dark">
            Informacoes do perfil
        </h2>

        <p class="text-body-secondary mt-2 mb-0">
            Atualize o nome e o e-mail principal usados na sua conta.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label fw-semibold">Nome</label>
            <input id="name" name="name" type="text" class="form-control form-control-lg" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <input id="email" name="email" type="email" class="form-control form-control-lg" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert alert-warning rounded-4 mt-3 mb-0">
                    <p class="mb-2">
                        Seu endereco de e-mail ainda nao foi verificado.
                    </p>

                        <button form="send-verification" class="btn btn-outline-warning btn-sm rounded-pill" type="submit">
                            Reenviar e-mail de verificacao
                        </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="small text-success fw-semibold mt-3 mb-0">
                            Um novo link de verificacao foi enviado para o seu e-mail.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar alteracoes</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small fw-semibold">Dados salvos com sucesso.</span>
            @endif
        </div>
    </form>
</section>
