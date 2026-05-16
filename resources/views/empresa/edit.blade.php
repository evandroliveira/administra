<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dados da Empresa</h2>
                <p class="mt-1 text-sm text-gray-500">Atualize as informações usadas no onboarding, cobrança e relatórios.</p>
            </div>

            <a href="{{ route('assinatura.show') }}" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Voltar para assinatura
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 xl:grid-cols-[1.5fr_0.9fr]">
            <div class="xl:col-span-2 rounded-3xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
                Usuários ativos: {{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}.
                Produtos cadastrados: {{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}.
            </div>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-6">
                    <h3 class="text-base font-semibold text-gray-900">Cadastro da Empresa</h3>
                    <p class="mt-1 text-sm text-gray-500">Esses dados alimentam a cobrança recorrente, a identificação da empresa e os relatórios exportados.</p>
                </div>

                <form method="POST" action="{{ route('empresa.update') }}" enctype="multipart/form-data" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div class="md:col-span-2">
                        <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input id="nome" name="nome" type="text" value="{{ old('nome', $empresa->nome) }}" required class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="documento" class="block text-sm font-medium text-gray-700">Documento</label>
                        <input id="documento" name="documento" type="text" value="{{ old('documento', $empresa->documento) }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input id="telefone" name="telefone" type="text" value="{{ old('telefone', $empresa->telefone) }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $empresa->email) }}" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="md:col-span-2 rounded-2xl border border-gray-200 p-4">
                        <label for="logo" class="block text-sm font-medium text-gray-700">Logo da empresa</label>

                        @if ($logoUrl)
                            <div class="mt-3 flex flex-col gap-3">
                                <img src="{{ $logoUrl }}" alt="Logo atual" class="h-20 w-auto max-w-[220px] rounded-xl border border-gray-200 bg-white p-2 object-contain">

                                <label class="inline-flex items-center gap-3 text-sm font-medium text-rose-700">
                                    <input type="hidden" name="remover_logo" value="0">
                                    <input type="checkbox" name="remover_logo" value="1" class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500" @checked(old('remover_logo') === '1')>
                                    Remover logo atual
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="remover_logo" value="0">
                        @endif

                        <input id="logo" name="logo" type="file" accept="image/*" class="mt-4 block w-full text-sm text-gray-600 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700">
                        <p class="mt-2 text-xs text-gray-500">Formatos aceitos: JPG, PNG, GIF ou WebP. Tamanho máximo de 2 MB.</p>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                            Salvar alterações
                        </button>

                        <a href="{{ route('assinatura.show') }}" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Voltar
                        </a>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Situação Atual</h3>

                    <dl class="mt-4 space-y-4 text-sm text-gray-600">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Slug</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $empresa->slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Status da empresa</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $empresa->ativa ? 'Ativa' : 'Inativa' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Plano</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $assinatura->plano->nome }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Status da assinatura</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $assinatura->status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Gateway</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $assinatura->gateway ?: 'Local' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Consumo Atual</h3>

                    <div class="mt-4 grid gap-4 text-sm text-gray-600">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Usuários</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Produtos</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>