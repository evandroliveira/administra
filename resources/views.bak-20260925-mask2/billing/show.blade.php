<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Billing</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Assinatura</h2>
                <p class="text-body-secondary mb-0">Empresa: {{ $empresa->nome }}</p>
            </div>
            <span class="badge text-bg-light border px-3 py-2 text-uppercase">{{ $providerLabel }}</span>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            @if (session('status'))
                <div class="alert alert-success rounded-4 mb-0">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger rounded-4 mb-0">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning rounded-4 mb-0">
                    {{ session('warning') }}
                </div>
            @endif

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4 p-lg-5 d-flex flex-column gap-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="h4 fw-semibold text-dark mb-2">Resumo da Assinatura</h3>
                            <p class="text-body-secondary mb-0">Situação atual do plano e do período de vigência.</p>
                        </div>
                        @php
                            $statusTone = match ($assinatura->status) {
                                'ativa' => 'text-bg-success',
                                'teste' => 'text-bg-info',
                                'inadimplente' => 'text-bg-warning',
                                'suspensa', 'cancelada' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $statusTone }} px-3 py-2 text-uppercase">{{ $assinatura->status_label }}</span>
                    </div>

                    <dl class="row g-3 small mb-0">
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Plano</dt><dd class="mb-0 text-dark">{{ $assinatura->plano->nome }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Valor mensal</dt><dd class="mb-0 text-dark">R$ {{ number_format((float) $assinatura->plano->valor_mensal, 2, ',', '.') }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Início</dt><dd class="mb-0 text-dark">{{ optional($assinatura->inicio_vigencia)->format('d/m/Y') ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Próximo fechamento</dt><dd class="mb-0 text-dark">{{ optional($assinatura->fim_periodo_atual)->format('d/m/Y') ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Teste até</dt><dd class="mb-0 text-dark">{{ optional($assinatura->trial_ends_at)->format('d/m/Y') ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Ativa até</dt><dd class="mb-0 text-dark">{{ optional($assinatura->ativa_ate)->format('d/m/Y') ?? '-' }}</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Carência</dt><dd class="mb-0 text-dark">{{ $billingGraceDays }} dia(s)</dd></div></div>
                        <div class="col-md-6 col-xxl-3"><div class="border rounded-4 p-3 h-100"><dt class="fw-semibold text-body-secondary mb-1">Gateway</dt><dd class="mb-0 text-dark">{{ $assinatura->gateway ?: 'Local' }}</dd></div></div>
                    </dl>

                    @if ($usuarioAdminEmpresa && $planoGratuitoAtivo)
                        <div class="alert alert-success rounded-4 mb-0">
                            <div class="small fw-semibold text-uppercase">Plano gratuito ativo</div>
                            <div class="mt-1 fs-6 fw-semibold text-success-emphasis">
                                @if ($assinatura->gateway_subscription_id)
                                    A recorrência no {{ $providerLabel }} foi suspensa enquanto a empresa permanecer neste plano.
                                @else
                                    Este plano não gera cobrança recorrente enquanto a empresa permanecer nele.
                                @endif
                            </div>
                            @if ($possuiFaturasHistoricas)
                                <div class="mt-2">
                                    Cobranças anteriores foram encerradas no histórico local e não exigem retomada enquanto o plano gratuito estiver ativo.
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($usuarioAdminEmpresa && $faturaEmAberto)
                        @php
                            $urlRetomada = $faturaEmAberto->checkout_url ?: $faturaEmAberto->invoice_url;
                        @endphp
                        <div class="alert alert-warning rounded-4 mb-0">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
                                <div>
                                    <div class="small fw-semibold text-uppercase">Pagamento pendente</div>
                                    <div class="mt-1 fs-6 fw-semibold text-warning-emphasis">Retome a cobrança atual da assinatura sem gerar uma nova empresa.</div>
                                    <div class="mt-2">
                                        Fatura {{ $faturaEmAberto->external_id ?: $faturaEmAberto->id }} • {{ $faturaEmAberto->status_label }} • vencimento {{ optional($faturaEmAberto->vencimento)->format('d/m/Y') ?? '-' }}.
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ $urlRetomada }}" target="_blank" rel="noopener" class="btn btn-warning rounded-pill px-4">
                                        Retomar pagamento atual
                                    </a>

                                    <form method="POST" action="{{ route('assinatura.cobranca.store') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning rounded-pill px-4">
                                            Atualizar cobrança no gateway
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($usuarioAdminEmpresa && ! $faturaEmAberto && $podeGerarNovaCobranca)
                        <div class="alert alert-danger rounded-4 mb-0">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
                                <div>
                                    <div class="small fw-semibold text-uppercase">Cobrança sem link reaproveitável</div>
                                    <div class="mt-1 fs-6 fw-semibold text-danger-emphasis">Gere uma nova cobrança quando o histórico atual não trouxer um checkout utilizável.</div>
                                    @if ($faturaSemLinkUtil)
                                        <div class="mt-2">
                                            Última fatura aberta sem link: {{ $faturaSemLinkUtil->external_id ?: $faturaSemLinkUtil->id }} • {{ $faturaSemLinkUtil->status_label }} • vencimento {{ optional($faturaSemLinkUtil->vencimento)->format('d/m/Y') ?? '-' }}.
                                        </div>
                                    @else
                                        <div class="mt-2">
                                            Não existe uma fatura aberta com link utilizável no histórico local; gere uma nova cobrança para retomar a regularização.
                                        </div>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('assinatura.cobranca.regenerate') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                                        Gerar nova cobrança
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if ($usuarioAdminEmpresa)
                        <div class="d-flex flex-wrap align-items-center gap-2 pt-2">
                            @unless ($planoGratuitoAtivo)
                                <form method="POST" action="{{ route('assinatura.cobranca.store') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-dark rounded-pill px-4">
                                        {{ $faturaEmAberto ? 'Sincronizar cobrança novamente' : ($podeGerarNovaCobranca ? 'Tentar localizar cobrança atual' : 'Ir para pagamento recorrente no cartão') }}
                                    </button>
                                </form>
                            @endunless

                            <a href="{{ route('empresa.edit') }}" class="btn btn-light rounded-pill border px-4">
                                Dados da empresa
                            </a>
                        </div>
                    @endif

                    @if (! $cobrancaConfigurada)
                        <div class="alert alert-warning rounded-4 mb-0">
                            O gateway de cobrança ainda não está configurado no Sistema Administrar. A assinatura segue em modo local até a migração da integração Asaas.
                        </div>
                    @endif
                        </div>
                </div>

                <div class="col-xl-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4 p-lg-5 d-flex flex-column gap-4">
                    <div>
                        <h3 class="h4 fw-semibold text-dark mb-2">Consumo do Plano</h3>
                        <p class="text-body-secondary mb-0">Capacidade atual da empresa com base no plano ativo.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-4 p-4 bg-light-subtle h-100">
                            <div class="small text-uppercase text-body-secondary fw-semibold">Usuários</div>
                            <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumoUso['usuarios_ativos'] }} / {{ $resumoUso['limite_usuarios'] }}</div>
                            <div class="text-body-secondary mt-2">Restam {{ $resumoUso['usuarios_restantes'] }} vaga(s).</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-4 bg-light-subtle h-100">
                            <div class="small text-uppercase text-body-secondary fw-semibold">Produtos</div>
                            <div class="fs-3 fw-semibold text-dark mt-2">{{ $resumoUso['produtos_cadastrados'] }} / {{ $resumoUso['limite_produtos'] }}</div>
                            <div class="text-body-secondary mt-2">Restam {{ $resumoUso['produtos_restantes'] }} cadastro(s).</div>
                            </div>
                        </div>
                    </div>

                    @if ($usuarioAdminEmpresa)
                        <div class="border rounded-4 p-4">
                            <h4 class="h5 fw-semibold text-dark mb-3">Plano e Recursos</h4>
                            <div class="row g-3 small">
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                    <div class="text-body-secondary">Promissórias</div>
                                    <div class="mt-1 fw-semibold text-dark">{{ $assinatura->plano->permite_promissoria ? 'Liberado' : 'Bloqueado' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                    <div class="text-body-secondary">PDF</div>
                                    <div class="mt-1 fw-semibold text-dark">{{ $assinatura->plano->permite_relatorios_pdf ? 'Liberado' : 'Bloqueado' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                    <div class="text-body-secondary">XLSX</div>
                                    <div class="mt-1 fw-semibold text-dark">{{ $assinatura->plano->permite_exportacao_xlsx ? 'Liberado' : 'Bloqueado' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div>
                                        <div class="small text-uppercase text-body-secondary fw-semibold">Planos disponíveis</div>
                                        <div class="text-body-secondary mt-1">A troca atualiza os limites da empresa imediatamente e, quando o gateway estiver configurado, resincroniza a assinatura no {{ $providerLabel }}.</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    @foreach ($planosDisponiveis as $planoDisponivel)
                                        @php
                                            $planoAtual = $assinatura->plano_id === $planoDisponivel->id;
                                            $planoDisponivelGratuito = (float) $planoDisponivel->valor_mensal <= 0;
                                        @endphp
                                        <div class="col-xl-6">
                                            <div class="rounded-4 border p-4 h-100 {{ $planoAtual ? 'border-primary-subtle bg-primary-subtle' : 'border-light bg-light-subtle' }}">
                                            <div class="d-flex align-items-start justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $planoDisponivel->nome }}</div>
                                                    <div class="mt-1 text-body-secondary">R$ {{ number_format((float) $planoDisponivel->valor_mensal, 2, ',', '.') }} / mês</div>
                                                    @if ($planoDisponivelGratuito)
                                                        <div class="mt-1 small fw-semibold text-uppercase text-success">Sem cobrança recorrente</div>
                                                    @endif
                                                </div>

                                                @if ($planoAtual)
                                                    <span class="badge text-bg-primary px-3 py-2 text-uppercase">Atual</span>
                                                @endif
                                            </div>

                                            @if ($planoDisponivel->descricao)
                                                <div class="mt-2 text-body-secondary">{{ $planoDisponivel->descricao }}</div>
                                            @endif

                                            <div class="row g-2 mt-2 small text-body-secondary">
                                                <div class="col-sm-6"><div class="rounded-3 border bg-white px-3 py-2 h-100">{{ $planoDisponivel->limite_usuarios }} usuário(s)</div></div>
                                                <div class="col-sm-6"><div class="rounded-3 border bg-white px-3 py-2 h-100">{{ $planoDisponivel->limite_produtos }} produto(s)</div></div>
                                                <div class="col-sm-6"><div class="rounded-3 border bg-white px-3 py-2 h-100">Promissórias: {{ $planoDisponivel->permite_promissoria ? 'sim' : 'não' }}</div></div>
                                                <div class="col-sm-6"><div class="rounded-3 border bg-white px-3 py-2 h-100">Exportações: {{ $planoDisponivel->permite_relatorios_pdf ? 'PDF' : '-' }}{{ $planoDisponivel->permite_exportacao_xlsx ? ' / XLSX' : '' }}</div></div>
                                            </div>

                                            @if ($planoAtual)
                                                <div class="mt-4 text-primary-emphasis fw-medium">Plano atual da empresa.</div>
                                            @else
                                                <form method="POST" action="{{ route('assinatura.plano.update') }}" class="mt-4">
                                                    @csrf
                                                    <input type="hidden" name="plano_id" value="{{ $planoDisponivel->id }}">
                                                    <button type="submit" class="btn btn-light border rounded-pill px-4">
                                                        {{ $planoDisponivelGratuito ? 'Migrar para plano gratuito' : 'Trocar para este plano' }}
                                                    </button>
                                                </form>
                                            @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                        </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-lg-5 border-bottom">
                <div class="d-flex align-items-center justify-content-between gap-4">
                    <div>
                        <h3 class="h4 fw-semibold text-dark mb-2">Faturas Recentes</h3>
                        <p class="text-body-secondary mb-0">Últimas cobranças registradas para a empresa.</p>
                    </div>
                </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Fatura</th>
                                <th class="px-4 py-3">Descrição</th>
                                <th class="px-4 py-3">Valor</th>
                                <th class="px-4 py-3">Vencimento</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($faturas as $fatura)
                                <tr>
                                    <td class="px-4 py-3">{{ $fatura->external_id ?: $fatura->id }}</td>
                                    <td class="px-4 py-3">
                                        <div>{{ $fatura->descricao ?: 'Assinatura recorrente' }}</div>
                                        @if (($fatura->payload['local_cancellation']['reason'] ?? null) === 'migrated_to_free_plan')
                                            <div class="mt-1 text-body-secondary">Encerrada ao migrar para plano gratuito.</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ optional($fatura->vencimento)->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $fatura->status_label }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $podeAbrirAcaoFatura = ! $planoGratuitoAtivo && ! in_array($fatura->status, ['cancelada', 'estornada'], true);
                                        @endphp
                                        @if ($podeAbrirAcaoFatura && $fatura->checkout_url)
                                            <a href="{{ $fatura->checkout_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-pill">Abrir</a>
                                        @elseif ($podeAbrirAcaoFatura && $fatura->invoice_url)
                                            <a href="{{ $fatura->invoice_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary rounded-pill">Ver</a>
                                        @else
                                            <span class="text-body-tertiary">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-5 text-center text-body-secondary">Nenhuma fatura registrada ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>