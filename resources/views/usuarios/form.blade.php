<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $modo === 'create' ? 'Novo usuário' : 'Editar usuário' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Defina perfil, acesso e dados de contato do colaborador.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 xl:grid-cols-[1.6fr_0.8fr]">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ $modo === 'create' ? route('usuarios.store') : route('usuarios.update', $usuarioEdicao) }}" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    @if ($modo === 'edit')
                        @method('PATCH')
                    @endif

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Usuário</label>
                        <input id="username" name="username" type="text" value="{{ $valores['username'] }}" required class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome completo</label>
                        <input id="name" name="name" type="text" value="{{ $valores['name'] }}" required class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ $valores['email'] }}" required class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="perfil" class="block text-sm font-medium text-gray-700">Perfil</label>
                        <select id="perfil" name="perfil" required class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($perfis as $perfil)
                                <option value="{{ $perfil->nome }}" @selected($valores['perfil'] === $perfil->nome)>{{ ucfirst($perfil->nome) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input id="telefone" name="telefone" type="text" value="{{ $valores['telefone'] }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700">
                            <input type="hidden" name="ativo" value="0">
                            <input type="checkbox" name="ativo" value="1" class="rounded border-gray-300 text-slate-900 shadow-sm focus:ring-slate-500" @checked($valores['ativo'])>
                            Usuário ativo
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                        <textarea id="endereco" name="endereco" rows="3" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ $valores['endereco'] }}</textarea>
                    </div>

                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                        <input id="cidade" name="cidade" type="text" value="{{ $valores['cidade'] }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="grid gap-5 md:grid-cols-[0.5fr_1fr]">
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700">UF</label>
                            <input id="estado" name="estado" type="text" maxlength="2" value="{{ $valores['estado'] }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div>
                            <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                            <input id="cep" name="cep" type="text" value="{{ $valores['cep'] }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">{{ $modo === 'create' ? 'Senha' : 'Nova senha' }}</label>
                        <input id="password" name="password" type="password" {{ $modo === 'create' ? 'required' : '' }} class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" {{ $modo === 'create' ? 'required' : '' }} class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                            {{ $modo === 'create' ? 'Cadastrar usuário' : 'Salvar alterações' }}
                        </button>

                        <a href="{{ route('usuarios.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Voltar
                        </a>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Resumo da equipe</h3>
                    <div class="mt-4 space-y-4 text-sm text-gray-600">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Usuários ativos</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumo['ativos'] }} / {{ $resumo['limite_usuarios'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Vagas restantes</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumo['usuarios_restantes'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Admins ativos</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumo['admins_ativos'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Inativos</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumo['inativos'] }}</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Regra crítica</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-600">
                        A empresa precisa manter pelo menos um administrador ativo. Ao trocar o perfil ou desativar um acesso, essa proteção é validada automaticamente.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>