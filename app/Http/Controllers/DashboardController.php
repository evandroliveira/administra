<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\ItemVenda;
use App\Models\Produto;
use App\Models\Promissoria;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    private const PERIODOS_VALIDOS = [30, 90, 180, 365];

    private const STATUS_VENDAS_VALIDOS = ['confirmada', 'concluida'];

    private const STATUS_FINANCEIROS_ABERTOS = ['aberta', 'parcial', 'vencida'];

    public function __invoke(Request $request)
    {
        $usuarioVendas = $request->user()?->usuarioVendas()->with('empresa')->first();

        abort_unless($usuarioVendas?->empresa, Response::HTTP_FORBIDDEN, 'Usuario sem empresa vinculada.');

        $empresa = $usuarioVendas->empresa;
        $periodoDias = $this->resolverPeriodo($request);
        $hoje = Carbon::today();
        $inicioPeriodo = $hoje->copy()->subDays($periodoDias - 1);
        $visoes = $this->resolverVisoes($request);

        $resumoBase = [
            'clientes_ativos' => Cliente::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->count(),
            'produtos_ativos' => $visoes['produtos']
                ? Produto::query()->where('empresa_id', $empresa->id)->where('ativo', true)->count()
                : null,
        ];

        $analiseComercial = [
            'cards' => [
                'faturamento_dia' => 0.0,
                'lucro_dia' => 0.0,
                'quantidade_vendas_dia' => 0,
                'faturamento_periodo' => 0.0,
                'lucro_periodo' => 0.0,
                'quantidade_vendas_periodo' => 0,
                'ticket_medio_periodo' => 0.0,
            ],
            'top_produtos' => collect(),
            'top_clientes' => collect(),
            'picos' => [
                'melhor_dia' => null,
                'melhor_dia_semana' => null,
                'melhor_horario' => null,
                'dias_campeoes' => collect(),
            ],
            'comparativo_mensal' => collect(),
            'melhor_mes' => null,
            'variacao_mes_atual_rotulo' => 'N/A',
        ];

        if ($visoes['comercial']) {
            $analiseComercial = $this->montarAnaliseComercial($empresa->id, $hoje, $inicioPeriodo);
        }

        $saudeFinanceira = [
            'total_receber_vencido' => 0.0,
            'quantidade_contas_receber_vencidas' => 0,
            'total_pagar_vencido' => 0.0,
            'quantidade_contas_pagar_vencidas' => 0,
            'clientes_inadimplentes' => 0,
            'promissorias_em_aberto' => 0,
        ];

        if ($visoes['financeiro']) {
            $saudeFinanceira = $this->montarSaudeFinanceira($empresa->id, $hoje);
        }

        $reposicao = [
            'resumo' => [
                'produtos_com_reposicao' => 0,
                'unidades_recomendadas' => 0,
                'valor_reposicao' => 0.0,
                'produtos_sem_giro' => 0,
                'dias_giro' => 30,
            ],
            'produtos' => collect(),
        ];

        if ($visoes['produtos']) {
            $reposicao = $this->montarReposicao($empresa->id, $hoje);
        }

        return view('dashboard', [
            'empresa' => $empresa,
            'periodoDias' => $periodoDias,
            'periodosDashboard' => collect(self::PERIODOS_VALIDOS)->map(fn (int $periodo) => [
                'valor' => $periodo,
                'label' => $periodo,
                'selecionado' => $periodo === $periodoDias,
            ]),
            'analisePeriodoInicio' => $inicioPeriodo->format('d/m/Y'),
            'analisePeriodoFim' => $hoje->format('d/m/Y'),
            'visoes' => $visoes,
            'acoesRapidas' => $this->acoesRapidas($visoes),
            'resumoBase' => $resumoBase,
            'analiseComercial' => $analiseComercial,
            'saudeFinanceira' => $saudeFinanceira,
            'reposicao' => $reposicao,
        ]);
    }

    private function resolverPeriodo(Request $request): int
    {
        $periodo = (int) $request->input('periodo', 90);

        return in_array($periodo, self::PERIODOS_VALIDOS, true) ? $periodo : 90;
    }

    private function resolverVisoes(Request $request): array
    {
        $user = $request->user();

        return [
            'clientes' => $user?->hasAnyRole(['admin', 'gerente', 'vendedor', 'recepcao']) ?? false,
            'comercial' => $user?->hasAnyRole(['admin', 'gerente', 'vendedor']) ?? false,
            'produtos' => $user?->hasAnyRole(['admin', 'gerente']) ?? false,
            'financeiro' => $user?->hasAnyRole(['admin', 'gerente']) ?? false,
            'relatorios' => $user?->hasAnyRole(['admin', 'gerente']) ?? false,
            'usuarios' => $user?->hasRole('admin') ?? false,
        ];
    }

    private function acoesRapidas(array $visoes): Collection
    {
        return collect([
            [
                'label' => 'Novo cliente',
                'description' => 'Cadastrar rapidamente um novo cliente.',
                'url' => route('clientes.create'),
                'show' => $visoes['clientes'],
                'tone' => 'border-sky-200 bg-sky-50 text-sky-900',
            ],
            [
                'label' => 'Nova venda',
                'description' => 'Abrir um novo atendimento comercial.',
                'url' => route('vendas.create'),
                'show' => $visoes['comercial'],
                'tone' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            ],
            [
                'label' => 'Novo produto',
                'description' => 'Adicionar item ao catalogo da empresa.',
                'url' => route('produtos.create'),
                'show' => $visoes['produtos'],
                'tone' => 'border-amber-200 bg-amber-50 text-amber-900',
            ],
            [
                'label' => 'Contas a receber',
                'description' => 'Acompanhar cobrancas e baixas do caixa.',
                'url' => route('contas.receber.index'),
                'show' => $visoes['financeiro'],
                'tone' => 'border-rose-200 bg-rose-50 text-rose-900',
            ],
            [
                'label' => 'Usuários',
                'description' => 'Gerenciar acessos e perfis da empresa.',
                'url' => route('usuarios.index'),
                'show' => $visoes['usuarios'],
                'tone' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-900',
            ],
            [
                'label' => 'Relatorios',
                'description' => 'Abrir a central de relatorios gerenciais.',
                'url' => route('relatorios.index'),
                'show' => $visoes['relatorios'],
                'tone' => 'border-slate-200 bg-slate-50 text-slate-900',
            ],
        ])->filter(fn (array $acao) => $acao['show'])->values();
    }

    private function montarAnaliseComercial(int $empresaId, Carbon $hoje, Carbon $inicioPeriodo): array
    {
        $inicioHoje = $hoje->copy()->startOfDay();
        $fimHoje = $hoje->copy()->endOfDay();
        $inicioAnalise = $inicioPeriodo->copy()->startOfDay();
        $fimAnalise = $hoje->copy()->endOfDay();

        $vendasHojeQuery = $this->vendasValidasQuery($empresaId)
            ->whereBetween('data_venda', [$inicioHoje, $fimHoje]);

        $vendasPeriodoQuery = $this->vendasValidasQuery($empresaId)
            ->whereBetween('data_venda', [$inicioAnalise, $fimAnalise]);

        $faturamentoDia = (float) (clone $vendasHojeQuery)->sum('total');
        $lucroDia = (float) (clone $vendasHojeQuery)->sum('lucro_total');
        $quantidadeVendasDia = (clone $vendasHojeQuery)->count();

        $faturamentoPeriodo = (float) (clone $vendasPeriodoQuery)->sum('total');
        $lucroPeriodo = (float) (clone $vendasPeriodoQuery)->sum('lucro_total');
        $quantidadeVendasPeriodo = (clone $vendasPeriodoQuery)->count();
        $ticketMedioPeriodo = $quantidadeVendasPeriodo > 0
            ? round($faturamentoPeriodo / $quantidadeVendasPeriodo, 2)
            : 0.0;

        $vendasPeriodo = (clone $vendasPeriodoQuery)
            ->get(['numero', 'cliente_id', 'data_venda', 'total', 'lucro_total']);

        $topProdutos = $this->montarTopProdutos($empresaId, $inicioAnalise, $fimAnalise);
        $topClientes = $this->montarTopClientes($empresaId, $inicioAnalise, $fimAnalise);
        $picos = $this->montarPicos($vendasPeriodo);
        $comparativoMensal = $this->montarComparativoMensal($empresaId, $hoje);

        $melhorMes = $comparativoMensal
            ->sortByDesc('faturamento')
            ->first(fn (array $mes) => (float) $mes['faturamento'] > 0);

        return [
            'cards' => [
                'faturamento_dia' => $faturamentoDia,
                'lucro_dia' => $lucroDia,
                'quantidade_vendas_dia' => $quantidadeVendasDia,
                'faturamento_periodo' => $faturamentoPeriodo,
                'lucro_periodo' => $lucroPeriodo,
                'quantidade_vendas_periodo' => $quantidadeVendasPeriodo,
                'ticket_medio_periodo' => $ticketMedioPeriodo,
            ],
            'top_produtos' => $topProdutos,
            'top_clientes' => $topClientes,
            'picos' => $picos,
            'comparativo_mensal' => $comparativoMensal,
            'melhor_mes' => $melhorMes,
            'variacao_mes_atual_rotulo' => (string) ($comparativoMensal->last()['variacao_rotulo'] ?? 'N/A'),
        ];
    }

    private function montarTopProdutos(int $empresaId, Carbon $inicio, Carbon $fim): Collection
    {
        $itens = ItemVenda::query()
            ->with('produto:id,nome,codigo')
            ->where('empresa_id', $empresaId)
            ->whereHas('venda', function ($query) use ($empresaId, $inicio, $fim) {
                $query->where('empresa_id', $empresaId)
                    ->whereIn('status', self::STATUS_VENDAS_VALIDOS)
                    ->whereBetween('data_venda', [$inicio, $fim]);
            })
            ->selectRaw('produto_id, SUM(quantidade) as quantidade_vendida, SUM(valor_total) as faturamento, SUM(lucro) as lucro')
            ->groupBy('produto_id')
            ->orderByDesc('lucro')
            ->orderByDesc('faturamento')
            ->limit(5)
            ->get();

        $lucroTotal = (float) $itens->sum(fn (ItemVenda $item) => (float) $item->lucro);

        return $itens->map(function (ItemVenda $item) use ($lucroTotal) {
            $faturamento = (float) $item->faturamento;
            $lucro = (float) $item->lucro;

            return [
                'nome' => $item->produto?->nome ?? 'Produto removido',
                'codigo' => $item->produto?->codigo ?? '-',
                'quantidade_vendida' => (int) $item->quantidade_vendida,
                'faturamento' => $faturamento,
                'lucro' => $lucro,
                'margem_percentual' => $faturamento > 0 ? round(($lucro / $faturamento) * 100, 1) : 0.0,
                'participacao_percentual' => $lucroTotal > 0 ? round(($lucro / $lucroTotal) * 100, 1) : 0.0,
            ];
        })->values();
    }

    private function montarTopClientes(int $empresaId, Carbon $inicio, Carbon $fim): Collection
    {
        $clientes = Venda::query()
            ->with('cliente:id,nome')
            ->where('empresa_id', $empresaId)
            ->whereIn('status', self::STATUS_VENDAS_VALIDOS)
            ->whereBetween('data_venda', [$inicio, $fim])
            ->selectRaw('cliente_id, COUNT(*) as quantidade_compras, SUM(total) as faturamento, SUM(lucro_total) as lucro')
            ->groupBy('cliente_id')
            ->orderByDesc('faturamento')
            ->orderByDesc('quantidade_compras')
            ->limit(5)
            ->get();

        $faturamentoTotal = (float) $clientes->sum(fn (Venda $venda) => (float) $venda->faturamento);

        return $clientes->map(function (Venda $venda) use ($faturamentoTotal) {
            $faturamento = (float) $venda->faturamento;
            $quantidadeCompras = (int) $venda->quantidade_compras;

            return [
                'nome' => $venda->cliente?->nome ?? 'Cliente removido',
                'quantidade_compras' => $quantidadeCompras,
                'faturamento' => $faturamento,
                'lucro' => (float) $venda->lucro,
                'ticket_medio' => $quantidadeCompras > 0 ? round($faturamento / $quantidadeCompras, 2) : 0.0,
                'participacao_percentual' => $faturamentoTotal > 0 ? round(($faturamento / $faturamentoTotal) * 100, 1) : 0.0,
            ];
        })->values();
    }

    private function montarPicos(Collection $vendasPeriodo): array
    {
        if ($vendasPeriodo->isEmpty()) {
            return [
                'melhor_dia' => null,
                'melhor_dia_semana' => null,
                'melhor_horario' => null,
                'dias_campeoes' => collect(),
            ];
        }

        $porDia = [];
        $porDiaSemana = [];
        $porHorario = [];

        foreach ($vendasPeriodo as $venda) {
            $dataVenda = $venda->data_venda instanceof Carbon ? $venda->data_venda : Carbon::parse($venda->data_venda);
            $chaveDia = $dataVenda->toDateString();
            $chaveSemana = $dataVenda->dayOfWeek;
            $chaveHora = (int) $dataVenda->format('H');

            $porDia[$chaveDia] ??= ['faturamento' => 0.0, 'lucro' => 0.0, 'quantidade' => 0];
            $porDiaSemana[$chaveSemana] ??= ['faturamento' => 0.0, 'quantidade' => 0];
            $porHorario[$chaveHora] ??= ['faturamento' => 0.0, 'quantidade' => 0];

            $porDia[$chaveDia]['faturamento'] += (float) $venda->total;
            $porDia[$chaveDia]['lucro'] += (float) $venda->lucro_total;
            $porDia[$chaveDia]['quantidade']++;

            $porDiaSemana[$chaveSemana]['faturamento'] += (float) $venda->total;
            $porDiaSemana[$chaveSemana]['quantidade']++;

            $porHorario[$chaveHora]['faturamento'] += (float) $venda->total;
            $porHorario[$chaveHora]['quantidade']++;
        }

        uasort($porDia, fn (array $a, array $b) => $b['faturamento'] <=> $a['faturamento']);
        uasort($porDiaSemana, fn (array $a, array $b) => $b['faturamento'] <=> $a['faturamento']);
        uasort($porHorario, fn (array $a, array $b) => $b['faturamento'] <=> $a['faturamento']);

        $nomesDiaSemana = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terca-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sabado',
        ];

        $maxFaturamentoDia = max(array_map(fn (array $item) => $item['faturamento'], $porDia));
        $diasCampeoes = collect($porDia)
            ->take(5)
            ->map(function (array $dados, string $dataRef) use ($maxFaturamentoDia) {
                $data = Carbon::parse($dataRef);

                return [
                    'rotulo' => $data->format('d/m/Y'),
                    'faturamento' => $dados['faturamento'],
                    'lucro' => $dados['lucro'],
                    'quantidade' => $dados['quantidade'],
                    'intensidade_percentual' => $maxFaturamentoDia > 0 ? round(($dados['faturamento'] / $maxFaturamentoDia) * 100, 1) : 0.0,
                ];
            })
            ->values();

        $indiceMelhorSemana = array_key_first($porDiaSemana);
        $melhorSemana = $indiceMelhorSemana !== null
            ? [
                'rotulo' => $nomesDiaSemana[(int) $indiceMelhorSemana] ?? 'Dia',
                'faturamento' => $porDiaSemana[$indiceMelhorSemana]['faturamento'],
                'quantidade' => $porDiaSemana[$indiceMelhorSemana]['quantidade'],
            ]
            : null;

        $indiceMelhorHorario = array_key_first($porHorario);
        $melhorHorario = $indiceMelhorHorario !== null
            ? [
                'rotulo' => sprintf('%02d:00', (int) $indiceMelhorHorario),
                'faturamento' => $porHorario[$indiceMelhorHorario]['faturamento'],
                'quantidade' => $porHorario[$indiceMelhorHorario]['quantidade'],
            ]
            : null;

        return [
            'melhor_dia' => $diasCampeoes->first(),
            'melhor_dia_semana' => $melhorSemana,
            'melhor_horario' => $melhorHorario,
            'dias_campeoes' => $diasCampeoes,
        ];
    }

    private function montarComparativoMensal(int $empresaId, Carbon $hoje): Collection
    {
        $referenciaMes = $hoje->copy()->startOfMonth();
        $meses = collect(range(5, 0, -1))
            ->map(fn (int $deslocamento) => $referenciaMes->copy()->subMonthsNoOverflow($deslocamento))
            ->push($referenciaMes->copy())
            ->values();

        $inicioComparativo = $meses->first()->copy()->startOfMonth();
        $fimComparativo = $referenciaMes->copy()->endOfMonth();

        $vendas = $this->vendasValidasQuery($empresaId)
            ->whereBetween('data_venda', [$inicioComparativo, $fimComparativo])
            ->get(['data_venda', 'total', 'lucro_total']);

        $agregados = $vendas->groupBy(fn (Venda $venda) => $venda->data_venda->format('Y-m'));
        $faturamentoAnterior = null;
        $maxFaturamento = 0.0;

        $comparativo = $meses->map(function (Carbon $mes) use ($agregados, &$faturamentoAnterior, &$maxFaturamento) {
            $chave = $mes->format('Y-m');
            $vendasMes = $agregados->get($chave, collect());
            $faturamento = (float) $vendasMes->sum('total');
            $lucro = (float) $vendasMes->sum('lucro_total');
            $quantidade = $vendasMes->count();
            $ticketMedio = $quantidade > 0 ? round($faturamento / $quantidade, 2) : 0.0;

            if ($faturamentoAnterior === null || $faturamentoAnterior == 0.0) {
                $variacaoRotulo = 'N/A';
            } else {
                $variacao = (($faturamento - $faturamentoAnterior) / $faturamentoAnterior) * 100;
                $variacaoRotulo = ($variacao > 0 ? '+' : '').number_format($variacao, 1, ',', '.').'%';
            }

            $faturamentoAnterior = $faturamento;
            $maxFaturamento = max($maxFaturamento, $faturamento);

            return [
                'rotulo' => $this->rotuloMes($mes),
                'faturamento' => $faturamento,
                'lucro' => $lucro,
                'quantidade' => $quantidade,
                'ticket_medio' => $ticketMedio,
                'variacao_rotulo' => $variacaoRotulo,
                'intensidade_percentual' => 0.0,
            ];
        })->values();

        return $comparativo->map(function (array $linha) use ($maxFaturamento) {
            $linha['intensidade_percentual'] = $maxFaturamento > 0
                ? round(($linha['faturamento'] / $maxFaturamento) * 100, 1)
                : 0.0;

            return $linha;
        });
    }

    private function montarSaudeFinanceira(int $empresaId, Carbon $hoje): array
    {
        $receberVencidas = ContaReceber::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', self::STATUS_FINANCEIROS_ABERTOS)
            ->whereDate('data_vencimento', '<', $hoje);

        $pagarVencidas = ContaPagar::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', self::STATUS_FINANCEIROS_ABERTOS)
            ->whereDate('data_vencimento', '<', $hoje);

        $totalReceber = (float) (clone $receberVencidas)
            ->get()
            ->sum(fn (ContaReceber $conta) => $conta->saldo_devedor);

        $totalPagar = (float) (clone $pagarVencidas)
            ->get()
            ->sum(fn (ContaPagar $conta) => $conta->saldo_devedor);

        $clientesInadimplentes = (clone $receberVencidas)
            ->pluck('cliente_id')
            ->filter()
            ->unique()
            ->count();

        return [
            'total_receber_vencido' => round($totalReceber, 2),
            'quantidade_contas_receber_vencidas' => (clone $receberVencidas)->count(),
            'total_pagar_vencido' => round($totalPagar, 2),
            'quantidade_contas_pagar_vencidas' => (clone $pagarVencidas)->count(),
            'clientes_inadimplentes' => $clientesInadimplentes,
            'promissorias_em_aberto' => Promissoria::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('status', self::STATUS_FINANCEIROS_ABERTOS)
                ->count(),
        ];
    }

    private function montarReposicao(int $empresaId, Carbon $hoje): array
    {
        $diasGiro = 30;
        $inicioGiro = $hoje->copy()->subDays($diasGiro - 1)->startOfDay();
        $fimGiro = $hoje->copy()->endOfDay();

        $giroPorProduto = ItemVenda::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('venda', function ($query) use ($empresaId, $inicioGiro, $fimGiro) {
                $query->where('empresa_id', $empresaId)
                    ->whereIn('status', self::STATUS_VENDAS_VALIDOS)
                    ->whereBetween('data_venda', [$inicioGiro, $fimGiro]);
            })
            ->selectRaw('produto_id, SUM(quantidade) as quantidade')
            ->groupBy('produto_id')
            ->pluck('quantidade', 'produto_id');

        $produtosSemGiro = 0;
        $unidadesRecomendadas = 0;
        $valorReposicao = 0.0;

        $produtos = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(function (Produto $produto) use ($giroPorProduto, $diasGiro, &$produtosSemGiro, &$unidadesRecomendadas, &$valorReposicao) {
                $quantidadeVendida = (int) ($giroPorProduto[$produto->id] ?? 0);
                if ($quantidadeVendida === 0) {
                    $produtosSemGiro++;
                }

                $estoqueIdeal = max((int) $produto->estoque_minimo, $quantidadeVendida);
                $sugestaoCompra = max($estoqueIdeal - (int) $produto->estoque_atual, 0);
                $custoBase = (float) $produto->custo_base_estoque;
                $valorSugerido = round($custoBase * $sugestaoCompra, 2);
                $giroMedio = round($quantidadeVendida / $diasGiro, 2);
                $cobertura = $giroMedio > 0 ? round((int) $produto->estoque_atual / $giroMedio, 1) : null;

                if ($sugestaoCompra > 0) {
                    $unidadesRecomendadas += $sugestaoCompra;
                    $valorReposicao += $valorSugerido;
                }

                return [
                    'nome' => $produto->nome,
                    'codigo' => $produto->codigo,
                    'estoque_atual' => (int) $produto->estoque_atual,
                    'estoque_ideal' => $estoqueIdeal,
                    'sugestao_compra' => $sugestaoCompra,
                    'giro_medio_diario' => $giroMedio,
                    'valor_reposicao' => $valorSugerido,
                    'cobertura_dias' => $cobertura,
                ];
            })
            ->filter(fn (array $produto) => $produto['sugestao_compra'] > 0)
            ->sortByDesc('sugestao_compra')
            ->values();

        return [
            'resumo' => [
                'produtos_com_reposicao' => $produtos->count(),
                'unidades_recomendadas' => $unidadesRecomendadas,
                'valor_reposicao' => round($valorReposicao, 2),
                'produtos_sem_giro' => $produtosSemGiro,
                'dias_giro' => $diasGiro,
            ],
            'produtos' => $produtos,
        ];
    }

    private function vendasValidasQuery(int $empresaId)
    {
        return Venda::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', self::STATUS_VENDAS_VALIDOS);
    }

    private function rotuloMes(Carbon $mes): string
    {
        $nomes = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return ($nomes[(int) $mes->month] ?? $mes->format('m')).'/'.$mes->format('y');
    }
}