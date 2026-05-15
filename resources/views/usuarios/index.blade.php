<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuários da Empresa</h2>
                <p class="mt-1 text-sm text-gray-500">Gerencie acessos, perfis e o status operacional da equipe.</p>
            </div>

            <a href="{{ route('usuarios.create') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                Novo usuário
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-3xl bg-slate-900 px-6 py-5 text-white shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Usuários totais</div>
                    <div class="mt-3 text-3xl font-semibold">{{ $resumo['total'] }}</div>
                </article>
                <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Ativos</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900">{{ $resumo['ativos'] }}</div>
                </article>
                <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Inativos</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900">{{ $resumo['inativos'] }}</div>
                </article>
                <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Admins ativos</div>
                    <div class="mt-3 text-3xl font-semibold text-gray-900">{{ $resumo['admins_ativos'] }}</div>
                </article>
            </section>

            <section class="overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-gray-200">
                @if ($usuarios->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                <tr>
                                    <th class="px-5 py-4">Usuário</th>
                                    <th class="px-5 py-4">Contato</th>
                                    <th class="px-5 py-4">Perfil</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-sm text-gray-700">
                                @foreach ($usuarios as $usuarioVendas)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-gray-900">{{ $usuarioVendas->user?->name ?? '-' }}</div>
                                            <div class="text-sm text-gray-500">{{ $usuarioVendas->user?->username ?? '-' }} • {{ $usuarioVendas->user?->email ?? '-' }}</div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div>{{ $usuarioVendas->telefone ?: '-' }}</div>
                                            <div class="text-sm text-gray-500">{{ $usuarioVendas->cidade ?: '-' }}{{ $usuarioVendas->estado ? ' • '.$usuarioVendas->estado : '' }}</div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700">
                                                {{ $usuarioVendas->perfil?->nome ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($usuarioVendas->ativo)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Ativo</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('usuarios.edit', $usuarioVendas) }}" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                                Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center text-sm text-gray-500">
                        Nenhum usuário vinculado à empresa ainda.
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>