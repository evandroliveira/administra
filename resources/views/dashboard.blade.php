<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $empresa->nome }} • base de {{ $analisePeriodoInicio }} ate {{ $analisePeriodoFim }}
                </p>
            </div>

            <form method="GET" class="flex items-end gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div>
                    <label for="periodo" class="block text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Janela analitica</label>
                    <select id="periodo" name="periodo" class="mt-2 block rounded-xl border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @foreach ($periodosDashboard as $periodo)
                            <option value="{{ $periodo['valor'] }}" @selected($periodo['selecionado'])>Ultimos {{ $periodo['label'] }} dias</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                    Atualizar
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($acoesRapidas as $acao)
                    <a href="{{ $acao['url'] }}" class="rounded-3xl border px-5 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $acao['tone'] }}">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em]">{{ $acao['label'] }}</div>
                        <p class="mt-2 text-sm leading-6 opacity-80">{{ $acao['description'] }}</p>
                    </a>
                @endforeach
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-3xl bg-slate-900 px-6 py-5 text-white shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Clientes ativos</div>
                    <div class="mt-3 text-3xl font-semibold">{{ $resumoBase['clientes_ativos'] }}</div>
                    <p class="mt-2 text-sm text-slate-300">Carteira ativa da empresa.</p>
                </article>

                @if ($visoes['produtos'])
                    <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Produtos ativos</div>
                        <div class="mt-3 text-3xl font-semibold text-gray-900">{{ $resumoBase['produtos_ativos'] }}</div>
                        <p class="mt-2 text-sm text-gray-500">Itens disponiveis no catalogo.</p>
                    </article>
                @endif

                @if ($visoes['comercial'])
                    <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Faturamento do dia</div>
                        <div class="mt-3 text-3xl font-semibold text-gray-900">R$ {{ number_format($analiseComercial['cards']['faturamento_dia'], 2, ',', '.') }}</div>
                        <p class="mt-2 text-sm text-gray-500">{{ $analiseComercial['cards']['quantidade_vendas_dia'] }} vendas validas hoje.</p>
                    </article>

                    <article class="rounded-3xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Analise comercial</div>
                        <div class="mt-3 text-3xl font-semibold text-gray-900">R$ {{ number_format($analiseComercial['cards']['faturamento_periodo'], 2, ',', '.') }}</div>
                        <p class="mt-2 text-sm text-gray-500">{{ $analiseComercial['cards']['quantidade_vendas_periodo'] }} vendas no periodo.</p>
                    </article>
                @endif
            </section>

            @if ($visoes['financeiro'])
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Saude financeira</h3>
                            <p class="text-sm text-gray-500">Visao rapida de contas vencidas e cobrancas abertas.</p>
                        </div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Financeiro</div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-3xl bg-rose-50 px-5 py-4 ring-1 ring-rose-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-500">Contas a receber vencidas</div>
                            <div class="mt-3 text-2xl font-semibold text-rose-900">R$ {{ number_format($saudeFinanceira['total_receber_vencido'], 2, ',', '.') }}</div>
                            <p class="mt-2 text-sm text-rose-700">{{ $saudeFinanceira['quantidade_contas_receber_vencidas'] }} titulos em atraso.</p>
                        </article>

                        <article class="rounded-3xl bg-amber-50 px-5 py-4 ring-1 ring-amber-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">Contas a pagar vencidas</div>
                            <div class="mt-3 text-2xl font-semibold text-amber-900">R$ {{ number_format($saudeFinanceira['total_pagar_vencido'], 2, ',', '.') }}</div>
                            <p class="mt-2 text-sm text-amber-700">{{ $saudeFinanceira['quantidade_contas_pagar_vencidas'] }} obrigacoes em aberto.</p>
                        </article>

                        <article class="rounded-3xl bg-slate-50 px-5 py-4 ring-1 ring-slate-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Clientes inadimplentes</div>
                            <div class="mt-3 text-2xl font-semibold text-slate-900">{{ $saudeFinanceira['clientes_inadimplentes'] }}</div>
                            <p class="mt-2 text-sm text-slate-600">Clientes com pelo menos um titulo vencido.</p>
                        </article>

                        <article class="rounded-3xl bg-emerald-50 px-5 py-4 ring-1 ring-emerald-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Promissorias em aberto</div>
                            <div class="mt-3 text-2xl font-semibold text-emerald-900">{{ $saudeFinanceira['promissorias_em_aberto'] }}</div>
                            <p class="mt-2 text-sm text-emerald-700">Titulos que ainda exigem acompanhamento.</p>
                        </article>
                    </div>
                </section>
            @endif

            @if ($visoes['comercial'])
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Analise comercial</h3>
                            <p class="text-sm text-gray-500">Resumo dos ultimos {{ $periodoDias }} dias com ranking e ritmo de vendas.</p>
                        </div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Comparativo mensal</div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-3xl bg-slate-50 px-5 py-4 ring-1 ring-slate-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lucro do periodo</div>
                            <div class="mt-3 text-2xl font-semibold text-slate-900">R$ {{ number_format($analiseComercial['cards']['lucro_periodo'], 2, ',', '.') }}</div>
                            <p class="mt-2 text-sm text-slate-600">Margem acumulada nas vendas validas.</p>
                        </article>

                        <article class="rounded-3xl bg-sky-50 px-5 py-4 ring-1 ring-sky-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">Ticket medio</div>
                            <div class="mt-3 text-2xl font-semibold text-sky-900">R$ {{ number_format($analiseComercial['cards']['ticket_medio_periodo'], 2, ',', '.') }}</div>
                            <p class="mt-2 text-sm text-sky-700">Media por venda confirmada ou concluida.</p>
                        </article>

                        <article class="rounded-3xl bg-violet-50 px-5 py-4 ring-1 ring-violet-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-600">Melhor mes recente</div>
                            <div class="mt-3 text-2xl font-semibold text-violet-900">{{ $analiseComercial['melhor_mes']['rotulo'] ?? 'Sem base' }}</div>
                            <p class="mt-2 text-sm text-violet-700">
                                @if ($analiseComercial['melhor_mes'])
                                    R$ {{ number_format($analiseComercial['melhor_mes']['faturamento'], 2, ',', '.') }} no melhor fechamento recente.
                                @else
                                    Ainda nao ha historico suficiente para comparar meses.
                                @endif
                            </p>
                        </article>

                        <article class="rounded-3xl bg-emerald-50 px-5 py-4 ring-1 ring-emerald-100">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Variacao do mes atual</div>
                            <div class="mt-3 text-2xl font-semibold text-emerald-900">{{ $analiseComercial['variacao_mes_atual_rotulo'] }}</div>
                            <p class="mt-2 text-sm text-emerald-700">Comparacao contra o mes imediatamente anterior.</p>
                        </article>
                    </div>

                    <div class="mt-6 grid gap-6 xl:grid-cols-2">
                        <article class="rounded-3xl border border-gray-200 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-semibold text-gray-900">Produtos mais lucrativos</h4>
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Top 5</span>
                            </div>

                            @if ($analiseComercial['top_produtos']->isNotEmpty())
                                <div class="mt-4 space-y-4">
                                    @foreach ($analiseComercial['top_produtos'] as $produto)
                                        <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-semibold text-gray-900">{{ $produto['nome'] }}</div>
                                                    <div class="text-sm text-gray-500">{{ $produto['codigo'] }} • {{ $produto['quantidade_vendida'] }} unidades</div>
                                                </div>
                                                <div class="text-right text-sm text-gray-500">
                                                    <div>R$ {{ number_format($produto['faturamento'], 2, ',', '.') }}</div>
                                                    <div class="font-semibold text-emerald-700">R$ {{ number_format($produto['lucro'], 2, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="mt-3 h-2 rounded-full bg-gray-200">
                                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($produto['participacao_percentual'], 100) }}%"></div>
                                            </div>
                                            <div class="mt-2 text-xs uppercase tracking-[0.18em] text-gray-400">Margem {{ number_format($produto['margem_percentual'], 1, ',', '.') }}%</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-4 rounded-2xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500">
                                    Nenhuma venda valida foi encontrada nesta janela para montar o ranking de produtos.
                                </div>
                            @endif
                        </article>

                        <article class="rounded-3xl border border-gray-200 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-semibold text-gray-900">Clientes que mais compram</h4>
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Top 5</span>
                            </div>

                            @if ($analiseComercial['top_clientes']->isNotEmpty())
                                <div class="mt-4 space-y-4">
                                    @foreach ($analiseComercial['top_clientes'] as $cliente)
                                        <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-semibold text-gray-900">{{ $cliente['nome'] }}</div>
                                                    <div class="text-sm text-gray-500">{{ $cliente['quantidade_compras'] }} compras • ticket medio R$ {{ number_format($cliente['ticket_medio'], 2, ',', '.') }}</div>
                                                </div>
                                                <div class="text-right text-sm text-gray-500">
                                                    <div class="font-semibold text-gray-900">R$ {{ number_format($cliente['faturamento'], 2, ',', '.') }}</div>
                                                    <div class="text-emerald-700">Lucro R$ {{ number_format($cliente['lucro'], 2, ',', '.') }}</div>
                                                </div>
                                            </div>
                                            <div class="mt-3 h-2 rounded-full bg-gray-200">
                                                <div class="h-2 rounded-full bg-sky-500" style="width: {{ min($cliente['participacao_percentual'], 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-4 rounded-2xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500">
                                    Ainda nao ha compras suficientes na janela escolhida para ranquear clientes.
                                </div>
                            @endif
                        </article>
                    </div>

                    <div class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_1.8fr]">
                        <article class="rounded-3xl border border-gray-200 p-5">
                            <h4 class="text-base font-semibold text-gray-900">Picos da operacao</h4>
                            <div class="mt-4 grid gap-4 md:grid-cols-3 xl:grid-cols-1">
                                <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Melhor dia</div>
                                    <div class="mt-2 text-lg font-semibold text-gray-900">{{ $analiseComercial['picos']['melhor_dia']['rotulo'] ?? 'Sem base' }}</div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        @if ($analiseComercial['picos']['melhor_dia'])
                                            R$ {{ number_format($analiseComercial['picos']['melhor_dia']['faturamento'], 2, ',', '.') }} em {{ $analiseComercial['picos']['melhor_dia']['quantidade'] }} vendas.
                                        @else
                                            Sem vendas suficientes para identificar pico diario.
                                        @endif
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Dia da semana lider</div>
                                    <div class="mt-2 text-lg font-semibold text-gray-900">{{ $analiseComercial['picos']['melhor_dia_semana']['rotulo'] ?? 'Sem base' }}</div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        @if ($analiseComercial['picos']['melhor_dia_semana'])
                                            R$ {{ number_format($analiseComercial['picos']['melhor_dia_semana']['faturamento'], 2, ',', '.') }} acumulados.
                                        @else
                                            Sem recorrencia semanal para medir.
                                        @endif
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Horario lider</div>
                                    <div class="mt-2 text-lg font-semibold text-gray-900">{{ $analiseComercial['picos']['melhor_horario']['rotulo'] ?? 'Sem base' }}</div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        @if ($analiseComercial['picos']['melhor_horario'])
                                            {{ $analiseComercial['picos']['melhor_horario']['quantidade'] }} vendas no horario mais forte.
                                        @else
                                            Sem concentracao por horario ainda.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-gray-200 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-semibold text-gray-900">Comparativo mensal</h4>
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">6 meses</span>
                            </div>

                            <div class="mt-4 space-y-4">
                                @foreach ($analiseComercial['comparativo_mensal'] as $mes)
                                    <div>
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <div>
                                                <span class="font-semibold text-gray-900">{{ $mes['rotulo'] }}</span>
                                                <span class="ml-2 text-gray-500">{{ $mes['quantidade'] }} vendas • ticket medio R$ {{ number_format($mes['ticket_medio'], 2, ',', '.') }}</span>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold text-gray-900">R$ {{ number_format($mes['faturamento'], 2, ',', '.') }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-gray-400">{{ $mes['variacao_rotulo'] }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-gray-200">
                                            <div class="h-2 rounded-full bg-slate-900" style="width: {{ min($mes['intensidade_percentual'], 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                </section>
            @endif

            @if ($visoes['produtos'])
                <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Reposicao inteligente</h3>
                            <p class="text-sm text-gray-500">Sugestoes baseadas no giro recente e no estoque minimo.</p>
                        </div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Base de {{ $reposicao['resumo']['dias_giro'] }} dias</div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-3xl bg-white px-5 py-4 ring-1 ring-gray-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Produtos com alerta</div>
                            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $reposicao['resumo']['produtos_com_reposicao'] }}</div>
                        </article>

                        <article class="rounded-3xl bg-white px-5 py-4 ring-1 ring-gray-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Unidades sugeridas</div>
                            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $reposicao['resumo']['unidades_recomendadas'] }}</div>
                        </article>

                        <article class="rounded-3xl bg-white px-5 py-4 ring-1 ring-gray-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Compra estimada</div>
                            <div class="mt-3 text-2xl font-semibold text-gray-900">R$ {{ number_format($reposicao['resumo']['valor_reposicao'], 2, ',', '.') }}</div>
                        </article>

                        <article class="rounded-3xl bg-white px-5 py-4 ring-1 ring-gray-200">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Sem giro recente</div>
                            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ $reposicao['resumo']['produtos_sem_giro'] }}</div>
                        </article>
                    </div>

                    @if ($reposicao['produtos']->isNotEmpty())
                        <div class="mt-6 overflow-hidden rounded-3xl border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3">Produto</th>
                                        <th class="px-4 py-3 text-right">Atual</th>
                                        <th class="px-4 py-3 text-right">Ideal</th>
                                        <th class="px-4 py-3 text-right">Sugestao</th>
                                        <th class="px-4 py-3 text-right">Cobertura</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white text-sm text-gray-700">
                                    @foreach ($reposicao['produtos'] as $produto)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900">{{ $produto['nome'] }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-gray-400">{{ $produto['codigo'] }} • giro {{ number_format($produto['giro_medio_diario'], 2, ',', '.') }}/dia</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">{{ $produto['estoque_atual'] }}</td>
                                            <td class="px-4 py-3 text-right">{{ $produto['estoque_ideal'] }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="font-semibold text-rose-700">{{ $produto['sugestao_compra'] }}</div>
                                                <div class="text-xs text-gray-400">R$ {{ number_format($produto['valor_reposicao'], 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                {{ $produto['cobertura_dias'] !== null ? number_format($produto['cobertura_dias'], 1, ',', '.') . ' dias' : 'Sem giro' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mt-6 rounded-3xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500">
                            Nenhum produto exige reposicao neste momento.
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
