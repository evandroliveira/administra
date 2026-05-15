<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assinatura</h2>
                <p class="text-sm text-gray-500 mt-1">Empresa: {{ $empresa->nome }}</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                {{ $providerLabel }}
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-amber-100 border border-amber-200 text-amber-800 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Resumo da Assinatura</h3>
                            <p class="mt-1 text-sm text-gray-500">Situação atual do plano e do período de vigência.</p>
                        </div>
                        @php
                            $statusTone = match ($assinatura->status) {
                                'ativa' => 'bg-emerald-100 text-emerald-700',
                                'teste' => 'bg-sky-100 text-sky-700',
                                'inadimplente' => 'bg-amber-100 text-amber-700',
                                'suspensa', 'cancelada' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $statusTone }}">{{ $assinatura->status_label }}</span>
                    </div>

                    <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 text-sm text-gray-700">
                        <div><dt class="font-semibold">Plano</dt><dd>{{ $assinatura->plano->nome }}</dd></div>
                        <div><dt class="font-semibold">Valor mensal</dt><dd>R$ {{ number_format((float) $assinatura->plano->valor_mensal, 2, ',', '.') }}</dd></div>
                        <div><dt class="font-semibold">Início</dt><dd>{{ optional($assinatura->inicio_vigencia)->format('d/m/Y') ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Próximo fechamento</dt><dd>{{ optional($assinatura->fim_periodo_atual)->format('d/m/Y') ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Teste até</dt><dd>{{ optional($assinatura->trial_ends_at)->format('d/m/Y') ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Ativa até</dt><dd>{{ optional($assinatura->ativa_ate)->format('d/m/Y') ?? '-' }}</dd></div>
                        <div><dt class="font-semibold">Carência</dt><dd>{{ $billingGraceDays }} dia(s)</dd></div>
                        <div><dt class="font-semibold">Gateway</dt><dd>{{ $assinatura->gateway ?: 'Local' }}</dd></div>
                    </dl>

                    @if ($usuarioAdminEmpresa)
                        <form method="POST" action="{{ route('assinatura.cobranca.store') }}" class="pt-2">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Ir para pagamento recorrente no cartão
                            </button>
                        </form>
                    @endif

                    @if (! $cobrancaConfigurada)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            O gateway de cobrança ainda não está configurado no Laravel. A assinatura segue em modo local até a migração da integração Asaas.
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Consumo do Plano</h3>
                        <p class="mt-1 text-sm text-gray-500">Capacidade atual da empresa com base no plano ativo.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Usuários</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}</div>
                            <div class="mt-2 text-sm text-slate-500">Restam {{ $resumoUso['usuarios_restantes'] }} vaga(s).</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Produtos</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}</div>
                            <div class="mt-2 text-sm text-slate-500">Restam {{ $resumoUso['produtos_restantes'] }} cadastro(s).</div>
                        </div>
                    </div>

                    @if ($usuarioAdminEmpresa)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <h4 class="font-semibold text-slate-900">Plano e Recursos</h4>
                            <div class="mt-4 grid gap-3 md:grid-cols-3 text-sm">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-slate-500">Promissórias</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $assinatura->plano->permite_promissoria ? 'Liberado' : 'Bloqueado' }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-slate-500">PDF</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $assinatura->plano->permite_relatorios_pdf ? 'Liberado' : 'Bloqueado' }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="text-slate-500">XLSX</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $assinatura->plano->permite_exportacao_xlsx ? 'Liberado' : 'Bloqueado' }}</div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
                                <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Plano disponível</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $planoPadrao->nome }}</div>
                                <div class="mt-1">R$ {{ number_format((float) $planoPadrao->valor_mensal, 2, ',', '.') }} / mês</div>
                                <div class="mt-1 text-slate-500">{{ $planoPadrao->limite_usuarios }} usuário(s) e {{ $planoPadrao->limite_produtos }} produto(s).</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Faturas Recentes</h3>
                        <p class="mt-1 text-sm text-gray-500">Últimas cobranças registradas para a empresa.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Fatura</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($faturas as $fatura)
                                <tr>
                                    <td class="px-4 py-3">{{ $fatura->external_id ?: $fatura->id }}</td>
                                    <td class="px-4 py-3">{{ $fatura->descricao ?: 'Assinatura recorrente' }}</td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ optional($fatura->vencimento)->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $fatura->status_label }}</td>
                                    <td class="px-4 py-3">
                                        @if ($fatura->checkout_url)
                                            <a href="{{ $fatura->checkout_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">Abrir</a>
                                        @elseif ($fatura->invoice_url)
                                            <a href="{{ $fatura->invoice_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Nenhuma fatura registrada ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>