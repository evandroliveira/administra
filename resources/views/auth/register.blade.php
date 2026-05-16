<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Onboarding SaaS</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Criar empresa e iniciar operação</h1>
            <p class="mt-2 text-sm text-slate-600">Cadastre a empresa, o primeiro administrador e já entre na assinatura inicial do sistema.</p>
        </div>

        <div class="rounded-3xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
            <div class="font-semibold">{{ $planoPadrao->nome }}</div>
            <div class="mt-1">R$ {{ number_format((float) $planoPadrao->valor_mensal, 2, ',', '.') }} / mês</div>
            <div class="mt-1 text-sky-800">Limite de {{ $planoPadrao->limite_usuarios }} usuários e {{ $planoPadrao->limite_produtos }} produtos.</div>
            @if ($cobrancaConfigurada)
                <div class="mt-2 text-sky-800">A cobrança recorrente será sincronizada no {{ $providerLabel }} logo após o cadastro.</div>
            @else
                <div class="mt-2 text-sky-800">O ambiente está em modo local: a assinatura será criada sem abrir checkout automático.</div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-8 space-y-8">
        @csrf

        <div class="grid gap-8 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Empresa</h2>
                    <p class="mt-1 text-sm text-slate-500">Esses dados serão usados no billing, nos relatórios e na identificação da conta.</p>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="nome_empresa" value="Nome da empresa" />
                        <x-text-input id="nome_empresa" class="mt-1 block w-full" type="text" name="nome_empresa" :value="old('nome_empresa')" required autofocus autocomplete="organization" />
                        <x-input-error :messages="$errors->get('nome_empresa')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="documento" value="CPF ou CNPJ" />
                        <x-text-input id="documento" class="mt-1 block w-full" type="text" name="documento" :value="old('documento')" autocomplete="off" />
                        <x-input-error :messages="$errors->get('documento')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email_empresa" value="E-mail da empresa" />
                        <x-text-input id="email_empresa" class="mt-1 block w-full" type="email" name="email_empresa" :value="old('email_empresa')" autocomplete="email" />
                        <x-input-error :messages="$errors->get('email_empresa')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="telefone_empresa" value="Telefone" />
                        <x-text-input id="telefone_empresa" class="mt-1 block w-full" type="text" name="telefone_empresa" :value="old('telefone_empresa')" autocomplete="tel" />
                        <x-input-error :messages="$errors->get('telefone_empresa')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="logo" value="Logo da empresa" />
                        <input id="logo" name="logo" type="file" accept="image/*" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" />
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        <p class="mt-2 text-xs text-slate-500">Opcional. Aceita JPG, PNG, GIF e WebP com até 2 MB.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Primeiro administrador</h2>
                    <p class="mt-1 text-sm text-slate-500">Esse usuário já entra com acesso administrativo completo à empresa criada.</p>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="name" value="Nome completo" />
                        <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="username" value="Usuário interno" />
                        <x-text-input id="username" class="mt-1 block w-full" type="text" name="username" :value="old('username')" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="E-mail de acesso" />
                        <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Senha" />
                        <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar senha" />
                        <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm font-medium text-slate-600 transition hover:text-slate-900" href="{{ route('login') }}">
                Voltar ao login
            </a>

            <x-primary-button class="justify-center px-6 py-3 text-sm">
                Criar empresa
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
