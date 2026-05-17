<x-guest-layout>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="rounded-4 border border-white border-opacity-25 bg-white shadow-lg p-4 p-lg-5 auth-surface">
                <span class="badge rounded-pill text-bg-primary px-3 py-2">Nova senha</span>
                <h1 class="h2 fw-semibold mt-3">Redefinir senha</h1>
                <p class="text-body-secondary mt-3">Defina uma nova senha para concluir a recuperação do acesso.</p>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4 mt-4" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" class="form-control form-control-lg" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                        @error('email')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Senha</label>
                        <input id="password" class="form-control form-control-lg" type="password" name="password" required autocomplete="new-password">
                        @error('password')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirmar senha</label>
                        <input id="password_confirmation" class="form-control form-control-lg" type="password" name="password_confirmation" required autocomplete="new-password">
                        @error('password_confirmation')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Salvar nova senha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
