<section>
    <header>
        <h2 class="h4 fw-semibold text-dark">
            Excluir conta
        </h2>

        <p class="text-body-secondary mt-2 mb-0">
            Ao excluir a conta, todos os dados e recursos associados serao removidos permanentemente.
        </p>
    </header>

    @if ($errors->userDeletion->isNotEmpty())
        <div class="alert alert-danger rounded-4 mt-4 mb-0">
            {{ $errors->userDeletion->first('password') }}
        </div>
    @endif

    <div class="rounded-4 border border-danger-subtle bg-danger-subtle p-4 mt-4">
        <div class="fw-semibold text-danger-emphasis">Zona de risco</div>
        <p class="small text-danger-emphasis mt-2 mb-3">Antes de continuar, tenha certeza de que nao precisa mais deste acesso.</p>

        <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
            Excluir conta
        </button>
    </div>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div>
                            <h2 class="h4 fw-semibold text-dark mb-1" id="confirmUserDeletionLabel">Tem certeza que deseja excluir sua conta?</h2>
                            <p class="text-body-secondary small mb-0">Essa acao remove permanentemente os dados vinculados ao seu usuario.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <label for="password" class="form-label fw-semibold">Senha</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control form-control-lg"
                            placeholder="Digite sua senha"
                        >

                        @if ($errors->userDeletion->has('password'))
                            <div class="text-danger small mt-2">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Excluir conta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->userDeletion->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('confirmUserDeletionModal');

                if (modalElement && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endif
</section>
