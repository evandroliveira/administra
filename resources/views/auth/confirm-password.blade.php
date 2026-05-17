<x-guest-layout>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="rounded-4 border border-white border-opacity-25 bg-white shadow-lg p-4 p-lg-5 auth-surface">
                <span class="badge rounded-pill text-bg-primary px-3 py-2">Confirmação</span>
                <h1 class="h2 fw-semibold mt-3">Confirmar senha</h1>
                <p class="text-body-secondary mt-3">Esta é uma área protegida. Confirme sua senha antes de continuar.</p>

                <form method="POST" action="{{ route('password.confirm') }}" class="mt-4">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Senha</label>
                        <input id="password" class="form-control form-control-lg" type="password" name="password" required autocomplete="current-password" autofocus>
                        @error('password')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
