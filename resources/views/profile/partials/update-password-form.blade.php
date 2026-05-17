<section>
    <header>
        <h2 class="h4 fw-semibold text-dark">
            Atualizar senha
        </h2>

        <p class="text-body-secondary mt-2 mb-0">
            Use uma senha forte e exclusiva para manter o acesso protegido.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label fw-semibold">Senha atual</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control form-control-lg" autocomplete="current-password">
            @if ($errors->updatePassword->has('current_password'))
                <div class="text-danger small mt-2">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label fw-semibold">Nova senha</label>
            <input id="update_password_password" name="password" type="password" class="form-control form-control-lg" autocomplete="new-password">
            @if ($errors->updatePassword->has('password'))
                <div class="text-danger small mt-2">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label fw-semibold">Confirmar nova senha</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control form-control-lg" autocomplete="new-password">
            @if ($errors->updatePassword->has('password_confirmation'))
                <div class="text-danger small mt-2">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar nova senha</button>

            @if (session('status') === 'password-updated')
                <span class="text-success small fw-semibold">Senha atualizada com sucesso.</span>
            @endif
        </div>
    </form>
</section>
