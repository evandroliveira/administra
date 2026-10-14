<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Conta</span>
            <h2 class="h1 fw-semibold text-dark mb-2">
                Perfil
            </h2>
            <p class="text-body-secondary mb-0">Atualize seus dados, a senha de acesso e as configuracoes da conta.</p>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                    @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4 p-lg-5">
                    @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                    @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
