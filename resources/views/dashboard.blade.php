<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-4">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Visao geral</span>
                <h2 class="h1 fw-semibold text-dark mb-2">
                    Dashboard
                </h2>
                <p class="text-body-secondary mb-0">
                    {{ $empresa->nome }} • base de {{ $analisePeriodoInicio }} ate {{ $analisePeriodoFim }}
                </p>
            </div>

            <form method="GET" class="dashboard-filter-shell row row-cols-1 row-cols-sm-auto g-3 align-items-end rounded-4 border bg-white shadow-sm p-3 m-0">
                <div class="col">
                    <label for="periodo" class="form-label text-uppercase fw-semibold small text-body-secondary mb-2">Janela analitica</label>
                    <select id="periodo" name="periodo" class="form-select form-select-lg">
                        @foreach ($periodosDashboard as $periodo)
                            <option value="{{ $periodo['valor'] }}" @selected($periodo['selecionado'])>Ultimos {{ $periodo['label'] }} dias</option>
                        @endforeach
                    </select>
                </div>

                <div class="col d-grid">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4">
                    Atualizar
                    </button>
                </div>
            </form>
        </div>
    </x-slot>

    @php
        $toneMap = [
            'border-sky-200 bg-sky-50 text-sky-900' => 'dashboard-tone-sky',
            'border-emerald-200 bg-emerald-50 text-emerald-900' => 'dashboard-tone-emerald',
            'border-amber-200 bg-amber-50 text-amber-900' => 'dashboard-tone-amber',
            'border-rose-200 bg-rose-50 text-rose-900' => 'dashboard-tone-rose',
            'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-900' => 'dashboard-tone-fuchsia',
            'border-slate-200 bg-slate-50 text-slate-900' => 'dashboard-tone-slate',
        ];
    @endphp

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            <section class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-5">
                @foreach ($acoesRapidas as $acao)
                    <div class="col">
                        <a href="{{ $acao['url'] }}" class="dashboard-quick-link card border-0 shadow-sm h-100 text-decoration-none {{ $toneMap[$acao['tone']] ?? 'dashboard-tone-slate' }}">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="small fw-semibold text-uppercase mb-0">{{ $acao['label'] }}</div>
                                    <span class="dashboard-quick-link-icon" aria-hidden="true">
                                        <i class="bi bi-arrow-up-right"></i>
                                    </span>
                                </div>
                                <p class="mb-0 small opacity-75">{{ $acao['description'] }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </section>

            <section class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-4">
                <div class="col">
                        <article class="dashboard-metric-card dashboard-metric-primary card border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="small fw-semibold text-uppercase text-white-50">Clientes ativos</div>
                            <div class="display-6 fw-semibold mt-3 dashboard-number">{{ $resumoBase['clientes_ativos'] }}</div>
                            <p class="small text-white-50 mt-2 mb-0">Carteira ativa da empresa.</p>
                        </div>
                    </article>
                </div>

                @if ($visoes['produtos'])
                    <div class="col">
                        <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                            <div class="card-body p-4 p-lg-5">
                                <div class="small fw-semibold text-uppercase text-body-secondary">Produtos ativos</div>
                                <div class="display-6 fw-semibold mt-3 text-dark dashboard-number">{{ $resumoBase['produtos_ativos'] }}</div>
                                <p class="small text-body-secondary mt-2 mb-0">Itens disponiveis no catalogo.</p>
                            </div>
                        </article>
                    </div>
                @endif

                @if ($visoes['comercial'])
                    <div class="col">
                        <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                            <div class="card-body p-4 p-lg-5">
                                <div class="small fw-semibold text-uppercase text-body-secondary">Faturamento do dia</div>
                                <div class="display-6 fw-semibold mt-3 text-dark dashboard-number">R$ {{ number_format($analiseComercial['cards']['faturamento_dia'], 2, ',', '.') }}</div>
                                <p class="small text-body-secondary mt-2 mb-0">{{ $analiseComercial['cards']['quantidade_vendas_dia'] }} vendas validas hoje.</p>
                            </div>
                        </article>
                    </div>

                    <div class="col">
                        <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                            <div class="card-body p-4 p-lg-5">
                                <div class="small fw-semibold text-uppercase text-body-secondary">Analise comercial</div>
                                <div class="display-6 fw-semibold mt-3 text-dark dashboard-number">R$ {{ number_format($analiseComercial['cards']['faturamento_periodo'], 2, ',', '.') }}</div>
                                <p class="small text-body-secondary mt-2 mb-0">{{ $analiseComercial['cards']['quantidade_vendas_periodo'] }} vendas no periodo.</p>
                            </div>
                        </article>
                    </div>
                @endif
            </section>

            @if ($visoes['financeiro'])
                <section class="card dashboard-panel border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-2">
                            <div>
                                <h3 class="h4 fw-semibold text-dark mb-1">Saude financeira</h3>
                                <p class="text-body-secondary mb-0">Visao rapida de contas vencidas e cobrancas abertas.</p>
                            </div>
                            <div class="small fw-semibold text-uppercase text-body-secondary">Financeiro</div>
                        </div>

                        <div class="row g-3 mt-1 row-cols-1 row-cols-md-2 row-cols-xl-4">
                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-danger card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Contas a receber vencidas</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">R$ {{ number_format($saudeFinanceira['total_receber_vencido'], 2, ',', '.') }}</div>
                                        <p class="small mt-2 mb-0">{{ $saudeFinanceira['quantidade_contas_receber_vencidas'] }} titulos em atraso.</p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-warning card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Contas a pagar vencidas</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">R$ {{ number_format($saudeFinanceira['total_pagar_vencido'], 2, ',', '.') }}</div>
                                        <p class="small mt-2 mb-0">{{ $saudeFinanceira['quantidade_contas_pagar_vencidas'] }} obrigacoes em aberto.</p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-slate card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Clientes inadimplentes</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">{{ $saudeFinanceira['clientes_inadimplentes'] }}</div>
                                        <p class="small mt-2 mb-0">Clientes com pelo menos um titulo vencido.</p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-success card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Promissorias em aberto</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">{{ $saudeFinanceira['promissorias_em_aberto'] }}</div>
                                        <p class="small mt-2 mb-0">Titulos que ainda exigem acompanhamento.</p>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if ($visoes['comercial'])
                <section class="card dashboard-panel border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-2">
                            <div>
                                <h3 class="h4 fw-semibold text-dark mb-1">Analise comercial</h3>
                                <p class="text-body-secondary mb-0">Resumo dos ultimos {{ $periodoDias }} dias com ranking e ritmo de vendas.</p>
                            </div>
                            <div class="small fw-semibold text-uppercase text-body-secondary">Comparativo mensal</div>
                        </div>

                        <div class="row g-3 mt-1 row-cols-1 row-cols-md-2 row-cols-xl-4">
                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-slate card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Lucro do periodo</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">R$ {{ number_format($analiseComercial['cards']['lucro_periodo'], 2, ',', '.') }}</div>
                                        <p class="small mt-2 mb-0">Margem acumulada nas vendas validas.</p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-info card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Ticket medio</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">R$ {{ number_format($analiseComercial['cards']['ticket_medio_periodo'], 2, ',', '.') }}</div>
                                        <p class="small mt-2 mb-0">Media por venda confirmada ou concluida.</p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-violet card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Melhor mes recente</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">{{ $analiseComercial['melhor_mes']['rotulo'] ?? 'Sem base' }}</div>
                                        <p class="small mt-2 mb-0">
                                            @if ($analiseComercial['melhor_mes'])
                                                R$ {{ number_format($analiseComercial['melhor_mes']['faturamento'], 2, ',', '.') }} no melhor fechamento recente.
                                            @else
                                                Ainda nao ha historico suficiente para comparar meses.
                                            @endif
                                        </p>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-mini-card dashboard-mini-success card border-0 h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase">Variacao do mes atual</div>
                                        <div class="h3 fw-semibold mt-3 dashboard-number">{{ $analiseComercial['variacao_mes_atual_rotulo'] }}</div>
                                        <p class="small mt-2 mb-0">Comparacao contra o mes imediatamente anterior.</p>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="row g-4 mt-1">
                            <div class="col-xl-6">
                                <article class="card border h-100 dashboard-inner-card">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                            <h4 class="h5 fw-semibold text-dark mb-0">Produtos mais lucrativos</h4>
                                            <span class="small fw-semibold text-uppercase text-body-secondary">Top 5</span>
                                        </div>

                                        @if ($analiseComercial['top_produtos']->isNotEmpty())
                                            <div class="d-flex flex-column gap-3">
                                                @foreach ($analiseComercial['top_produtos'] as $produto)
                                                    <div class="dashboard-list-card rounded-4 p-3">
                                                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3">
                                                            <div>
                                                                <div class="fw-semibold text-dark">{{ $produto['nome'] }}</div>
                                                                <div class="small text-body-secondary">{{ $produto['codigo'] }} • {{ $produto['quantidade_vendida'] }} unidades</div>
                                                            </div>
                                                            <div class="text-sm-end small text-body-secondary">
                                                                <div>R$ {{ number_format($produto['faturamento'], 2, ',', '.') }}</div>
                                                                <div class="fw-semibold text-success">R$ {{ number_format($produto['lucro'], 2, ',', '.') }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="progress dashboard-progress mt-3" role="progressbar" aria-label="Participacao do produto" aria-valuenow="{{ min($produto['participacao_percentual'], 100) }}" aria-valuemin="0" aria-valuemax="100">
                                                            <div class="progress-bar bg-success" style="width: {{ min($produto['participacao_percentual'], 100) }}%"></div>
                                                        </div>
                                                        <div class="small text-uppercase text-body-secondary mt-2">Margem {{ number_format($produto['margem_percentual'], 1, ',', '.') }}%</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="dashboard-empty-state rounded-4 p-4">
                                                Nenhuma venda valida foi encontrada nesta janela para montar o ranking de produtos.
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            </div>

                            <div class="col-xl-6">
                                <article class="card border h-100 dashboard-inner-card">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                            <h4 class="h5 fw-semibold text-dark mb-0">Clientes que mais compram</h4>
                                            <span class="small fw-semibold text-uppercase text-body-secondary">Top 5</span>
                                        </div>

                                        @if ($analiseComercial['top_clientes']->isNotEmpty())
                                            <div class="d-flex flex-column gap-3">
                                                @foreach ($analiseComercial['top_clientes'] as $cliente)
                                                    <div class="dashboard-list-card rounded-4 p-3">
                                                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3">
                                                            <div>
                                                                <div class="fw-semibold text-dark">{{ $cliente['nome'] }}</div>
                                                                <div class="small text-body-secondary">{{ $cliente['quantidade_compras'] }} compras • ticket medio R$ {{ number_format($cliente['ticket_medio'], 2, ',', '.') }}</div>
                                                            </div>
                                                            <div class="text-sm-end small text-body-secondary">
                                                                <div class="fw-semibold text-dark">R$ {{ number_format($cliente['faturamento'], 2, ',', '.') }}</div>
                                                                <div class="text-success">Lucro R$ {{ number_format($cliente['lucro'], 2, ',', '.') }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="progress dashboard-progress mt-3" role="progressbar" aria-label="Participacao do cliente" aria-valuenow="{{ min($cliente['participacao_percentual'], 100) }}" aria-valuemin="0" aria-valuemax="100">
                                                            <div class="progress-bar bg-info" style="width: {{ min($cliente['participacao_percentual'], 100) }}%"></div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="dashboard-empty-state rounded-4 p-4">
                                                Ainda nao ha compras suficientes na janela escolhida para ranquear clientes.
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="row g-4 mt-1">
                            <div class="col-xl-5">
                                <article class="card border h-100 dashboard-inner-card">
                                    <div class="card-body p-4">
                                        <h4 class="h5 fw-semibold text-dark mb-4">Picos da operacao</h4>
                                        <div class="row g-3 row-cols-1 row-cols-md-3 row-cols-xl-1">
                                            <div class="col">
                                                <div class="dashboard-list-card rounded-4 p-3 h-100">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary">Melhor dia</div>
                                                    <div class="h5 fw-semibold text-dark mt-2 mb-0">{{ $analiseComercial['picos']['melhor_dia']['rotulo'] ?? 'Sem base' }}</div>
                                                    <p class="small text-body-secondary mt-2 mb-0">
                                                        @if ($analiseComercial['picos']['melhor_dia'])
                                                            R$ {{ number_format($analiseComercial['picos']['melhor_dia']['faturamento'], 2, ',', '.') }} em {{ $analiseComercial['picos']['melhor_dia']['quantidade'] }} vendas.
                                                        @else
                                                            Sem vendas suficientes para identificar pico diario.
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="col">
                                                <div class="dashboard-list-card rounded-4 p-3 h-100">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary">Dia da semana lider</div>
                                                    <div class="h5 fw-semibold text-dark mt-2 mb-0">{{ $analiseComercial['picos']['melhor_dia_semana']['rotulo'] ?? 'Sem base' }}</div>
                                                    <p class="small text-body-secondary mt-2 mb-0">
                                                        @if ($analiseComercial['picos']['melhor_dia_semana'])
                                                            R$ {{ number_format($analiseComercial['picos']['melhor_dia_semana']['faturamento'], 2, ',', '.') }} acumulados.
                                                        @else
                                                            Sem recorrencia semanal para medir.
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="col">
                                                <div class="dashboard-list-card rounded-4 p-3 h-100">
                                                    <div class="small fw-semibold text-uppercase text-body-secondary">Horario lider</div>
                                                    <div class="h5 fw-semibold text-dark mt-2 mb-0">{{ $analiseComercial['picos']['melhor_horario']['rotulo'] ?? 'Sem base' }}</div>
                                                    <p class="small text-body-secondary mt-2 mb-0">
                                                        @if ($analiseComercial['picos']['melhor_horario'])
                                                            {{ $analiseComercial['picos']['melhor_horario']['quantidade'] }} vendas no horario mais forte.
                                                        @else
                                                            Sem concentracao por horario ainda.
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div class="col-xl-7">
                                <article class="card border h-100 dashboard-inner-card">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                            <h4 class="h5 fw-semibold text-dark mb-0">Comparativo mensal</h4>
                                            <span class="small fw-semibold text-uppercase text-body-secondary">6 meses</span>
                                        </div>

                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($analiseComercial['comparativo_mensal'] as $mes)
                                                <div>
                                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 small">
                                                        <div>
                                                            <span class="fw-semibold text-dark">{{ $mes['rotulo'] }}</span>
                                                            <span class="text-body-secondary ms-sm-2">{{ $mes['quantidade'] }} vendas • ticket medio R$ {{ number_format($mes['ticket_medio'], 2, ',', '.') }}</span>
                                                        </div>
                                                        <div class="text-sm-end">
                                                            <div class="fw-semibold text-dark">R$ {{ number_format($mes['faturamento'], 2, ',', '.') }}</div>
                                                            <div class="small text-uppercase text-body-secondary">{{ $mes['variacao_rotulo'] }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="progress dashboard-progress mt-2" role="progressbar" aria-label="Intensidade mensal" aria-valuenow="{{ min($mes['intensidade_percentual'], 100) }}" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar bg-dark" style="width: {{ min($mes['intensidade_percentual'], 100) }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if ($visoes['produtos'])
                <section class="card dashboard-panel border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-2">
                            <div>
                                <h3 class="h4 fw-semibold text-dark mb-1">Reposicao inteligente</h3>
                                <p class="text-body-secondary mb-0">Sugestoes baseadas no giro recente e no estoque minimo.</p>
                            </div>
                            <div class="small fw-semibold text-uppercase text-body-secondary">Base de {{ $reposicao['resumo']['dias_giro'] }} dias</div>
                        </div>

                        <div class="row g-3 mt-1 row-cols-1 row-cols-md-2 row-cols-xl-4">
                            <div class="col">
                                <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase text-body-secondary">Produtos com alerta</div>
                                        <div class="h3 fw-semibold mt-3 text-dark dashboard-number">{{ $reposicao['resumo']['produtos_com_reposicao'] }}</div>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase text-body-secondary">Unidades sugeridas</div>
                                        <div class="h3 fw-semibold mt-3 text-dark dashboard-number">{{ $reposicao['resumo']['unidades_recomendadas'] }}</div>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase text-body-secondary">Compra estimada</div>
                                        <div class="h3 fw-semibold mt-3 text-dark dashboard-number">R$ {{ number_format($reposicao['resumo']['valor_reposicao'], 2, ',', '.') }}</div>
                                    </div>
                                </article>
                            </div>

                            <div class="col">
                                <article class="dashboard-metric-card card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="small fw-semibold text-uppercase text-body-secondary">Sem giro recente</div>
                                        <div class="h3 fw-semibold mt-3 text-dark dashboard-number">{{ $reposicao['resumo']['produtos_sem_giro'] }}</div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        @if ($reposicao['produtos']->isNotEmpty())
                            <div class="table-responsive mt-4 rounded-4 border">
                                <table class="table table-hover align-middle mb-0 bg-white">
                                    <thead class="table-light small text-uppercase">
                                        <tr>
                                            <th class="px-4 py-3">Produto</th>
                                            <th class="px-4 py-3 text-end">Atual</th>
                                            <th class="px-4 py-3 text-end">Ideal</th>
                                            <th class="px-4 py-3 text-end">Sugestao</th>
                                            <th class="px-4 py-3 text-end">Cobertura</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach ($reposicao['produtos'] as $produto)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="fw-semibold text-dark">{{ $produto['nome'] }}</div>
                                                    <div class="small text-uppercase text-body-secondary">{{ $produto['codigo'] }} • giro {{ number_format($produto['giro_medio_diario'], 2, ',', '.') }}/dia</div>
                                                </td>
                                                <td class="px-4 py-3 text-end">{{ $produto['estoque_atual'] }}</td>
                                                <td class="px-4 py-3 text-end">{{ $produto['estoque_ideal'] }}</td>
                                                <td class="px-4 py-3 text-end">
                                                    <div class="fw-semibold text-danger">{{ $produto['sugestao_compra'] }}</div>
                                                    <div class="small text-body-secondary">R$ {{ number_format($produto['valor_reposicao'], 2, ',', '.') }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-end">
                                                    {{ $produto['cobertura_dias'] !== null ? number_format($produto['cobertura_dias'], 1, ',', '.') . ' dias' : 'Sem giro' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="dashboard-empty-state rounded-4 p-4 mt-4">
                                Nenhum produto exige reposicao neste momento.
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
