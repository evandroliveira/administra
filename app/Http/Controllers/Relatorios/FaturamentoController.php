<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Promissoria;
use App\Support\Billing\BillingService;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FaturamentoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        $billingService = app(BillingService::class);
        $assinatura = $billingService->currentSubscriptionByEmpresaId($empresaId);
        $permiteXlsx = $billingService->featureEnabled($assinatura, 'permite_exportacao_xlsx');
        $permitePdf = $billingService->featureEnabled($assinatura, 'permite_relatorios_pdf');
        $permitePromissoria = $billingService->featureEnabled($assinatura, 'permite_promissoria');

        $hoje = Carbon::today();
        $dataInicio = Carbon::parse($request->string('data_inicio')->toString() ?: $hoje->copy()->subDays(30)->toDateString())->startOfDay();
        $dataFim = Carbon::parse($request->string('data_fim')->toString() ?: $hoje->toDateString())->endOfDay();

        $vendas = Venda::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', ['confirmada', 'concluida'])
            ->whereBetween('data_venda', [$dataInicio, $dataFim])
            ->with(['cliente'])
            ->orderByDesc('data_venda')
            ->get();

        $promissorias = $permitePromissoria
            ? Promissoria::query()
                ->where('empresa_id', $empresaId)
                ->whereHas('venda', function ($query) use ($dataInicio, $dataFim) {
                    $query->whereIn('status', ['confirmada', 'concluida'])
                        ->whereBetween('data_venda', [$dataInicio, $dataFim]);
                })
                ->with(['cliente', 'venda'])
                ->orderByDesc('id')
                ->get()
            : collect();

        $vendasPorDia = $vendas
            ->groupBy(fn (Venda $venda) => optional($venda->data_venda)->format('Y-m-d'))
            ->map(function ($grupo, $data) {
                $primeiraVenda = $grupo->first();

                return [
                    'data' => $data ? Carbon::parse($data) : null,
                    'quantidade' => $grupo->count(),
                    'total' => (float) $grupo->sum(fn (Venda $venda) => (float) $venda->total),
                    'lucro' => (float) $grupo->sum(fn (Venda $venda) => (float) $venda->lucro_total),
                    'clientes' => $grupo->pluck('cliente.nome')->filter()->unique()->values(),
                    'status' => $primeiraVenda?->status,
                ];
            })
            ->sortByDesc(fn (array $linha) => $linha['data']?->timestamp ?? 0)
            ->values();

        $resumo = [
            'total_faturamento' => (float) $vendas->sum(fn (Venda $venda) => (float) $venda->total),
            'total_lucro' => (float) $vendas->sum(fn (Venda $venda) => (float) $venda->lucro_total),
            'quantidade_vendas' => $vendas->count(),
            'quantidade_promissorias' => $promissorias->count(),
            'total_financiado_promissorias' => (float) $promissorias->sum(fn (Promissoria $promissoria) => (float) $promissoria->valor_financiado),
            'total_entrada_promissorias' => (float) $promissorias->sum(fn (Promissoria $promissoria) => (float) $promissoria->valor_entrada),
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportarCsv($dataInicio, $dataFim, $resumo, $vendasPorDia, $promissorias);
        }

        if ($request->query('export') === 'xlsx') {
            if (! $permiteXlsx) {
                return $billingService->deniedFeatureResponse($request, 'Seu plano atual não permite exportação em XLSX.');
            }

            return $this->exportarXlsx($dataInicio, $dataFim, $resumo, $vendasPorDia, $promissorias);
        }

        $empresa = Empresa::query()->find($empresaId);

        if ($request->query('export') === 'pdf') {
            if (! $permitePdf) {
                return $billingService->deniedFeatureResponse($request, 'Seu plano atual não permite exportação em PDF.');
            }

            return $this->exportarPdf($empresa, $dataInicio, $dataFim, $resumo, $vendasPorDia, $promissorias);
        }

        return view('relatorios.faturamento', [
            'empresa' => $empresa,
            'dataInicio' => $dataInicio->toDateString(),
            'dataFim' => $dataFim->toDateString(),
            'resumo' => $resumo,
            'vendasPorDia' => $vendasPorDia,
            'promissorias' => $promissorias,
            'hubUrl' => route('relatorios.index', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
            ]),
            'exportXlsxUrl' => $permiteXlsx ? route('relatorios.faturamento', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'xlsx',
            ]) : null,
            'exportPdfUrl' => $permitePdf ? route('relatorios.faturamento', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'pdf',
            ]) : null,
            'exportCsvUrl' => route('relatorios.faturamento', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'csv',
            ]),
        ]);
    }

    private function exportarCsv(Carbon $dataInicio, Carbon $dataFim, array $resumo, $vendasPorDia, $promissorias)
    {
        $fileName = sprintf('relatorio_faturamento_%s_%s.csv', $dataInicio->toDateString(), $dataFim->toDateString());

        return response()->streamDownload(function () use ($dataInicio, $dataFim, $resumo, $vendasPorDia, $promissorias) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Relatório de Faturamento'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Período inicial', $dataInicio->format('d/m/Y')], ';');
            fputcsv($saida, ['Período final', $dataFim->format('d/m/Y')], ';');
            fputcsv($saida, ['Faturamento total', number_format($resumo['total_faturamento'], 2, ',', '.')], ';');
            fputcsv($saida, ['Lucro total', number_format($resumo['total_lucro'], 2, ',', '.')], ';');
            fputcsv($saida, ['Quantidade de vendas', $resumo['quantidade_vendas']], ';');
            fputcsv($saida, ['Vendas com promissória', $resumo['quantidade_promissorias']], ';');
            fputcsv($saida, ['Total financiado', number_format($resumo['total_financiado_promissorias'], 2, ',', '.')], ';');
            fputcsv($saida, ['Total de entradas', number_format($resumo['total_entrada_promissorias'], 2, ',', '.')], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['Vendas por dia'], ';');
            fputcsv($saida, ['Data', 'Quantidade', 'Faturamento', 'Lucro', 'Clientes'], ';');
            foreach ($vendasPorDia as $linha) {
                fputcsv($saida, [
                    $linha['data']?->format('d/m/Y') ?? '',
                    $linha['quantidade'],
                    number_format($linha['total'], 2, ',', '.'),
                    number_format($linha['lucro'], 2, ',', '.'),
                    $linha['clientes']->implode(' | '),
                ], ';');
            }

            if ($promissorias->isNotEmpty()) {
                fputcsv($saida, [], ';');
                fputcsv($saida, ['Promissórias'], ';');
                fputcsv($saida, ['Data', 'Documento', 'Cliente', 'Entrada', 'Financiado', 'Multa (%)', 'Juros (%/dia)', 'Status'], ';');
                foreach ($promissorias as $promissoria) {
                    fputcsv($saida, [
                        optional($promissoria->venda?->data_venda)->format('d/m/Y') ?? '',
                        $promissoria->numero_documento,
                        $promissoria->cliente?->nome,
                        number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                        number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                        number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.'),
                        number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.'),
                        ucfirst((string) $promissoria->status),
                    ], ';');
                }
            }

            fclose($saida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Carbon $dataInicio, Carbon $dataFim, array $resumo, Collection $vendasPorDia, Collection $promissorias)
    {
        $sections = [[
            'title' => 'Vendas por Dia',
            'sheet_title' => 'Vendas por Dia',
            'columns' => [
                ['label' => 'Data'],
                ['label' => 'Quantidade'],
                ['label' => 'Faturamento'],
                ['label' => 'Lucro'],
                ['label' => 'Clientes'],
            ],
            'rows' => $vendasPorDia->map(fn (array $linha) => [
                $linha['data']?->format('d/m/Y') ?? '-',
                (string) $linha['quantidade'],
                number_format($linha['total'], 2, ',', '.'),
                number_format($linha['lucro'], 2, ',', '.'),
                $linha['clientes']->implode(', '),
            ])->all(),
        ]];

        if ($promissorias->isNotEmpty()) {
            $sections[] = [
                'title' => 'Promissórias',
                'sheet_title' => 'Promissorias',
                'columns' => [
                    ['label' => 'Data'],
                    ['label' => 'Documento'],
                    ['label' => 'Cliente'],
                    ['label' => 'Entrada'],
                    ['label' => 'Financiado'],
                    ['label' => 'Multa (%)'],
                    ['label' => 'Juros (%/dia)'],
                    ['label' => 'Status'],
                ],
                'rows' => $promissorias->map(fn (Promissoria $promissoria) => [
                    optional($promissoria->venda?->data_venda)->format('d/m/Y') ?? '-',
                    $promissoria->numero_documento,
                    $promissoria->cliente?->nome ?? '-',
                    number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                    number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                    number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.'),
                    number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.'),
                    ucfirst((string) $promissoria->status),
                ])->all(),
            ];
        }

        return app(XlsxExporter::class)->download(
            sprintf('relatorio_faturamento_%s_%s.xlsx', $dataInicio->toDateString(), $dataFim->toDateString()),
            'Relatório de Faturamento',
            [
                ['Período inicial', $dataInicio->format('d/m/Y')],
                ['Período final', $dataFim->format('d/m/Y')],
                ['Faturamento total', number_format($resumo['total_faturamento'], 2, ',', '.')],
                ['Lucro total', number_format($resumo['total_lucro'], 2, ',', '.')],
                ['Quantidade de vendas', (string) $resumo['quantidade_vendas']],
                ['Vendas com promissória', (string) $resumo['quantidade_promissorias']],
                ['Total financiado', number_format($resumo['total_financiado_promissorias'], 2, ',', '.')],
                ['Total de entradas', number_format($resumo['total_entrada_promissorias'], 2, ',', '.')],
            ],
            $sections
        );
    }

    private function exportarPdf(?Empresa $empresa, Carbon $dataInicio, Carbon $dataFim, array $resumo, Collection $vendasPorDia, Collection $promissorias)
    {
        $sections = [
            [
                'title' => 'Vendas por Dia',
                'columns' => [
                    ['label' => 'Data', 'width' => '12%'],
                    ['label' => 'Quantidade', 'class' => 'num', 'width' => '12%'],
                    ['label' => 'Faturamento', 'class' => 'num', 'width' => '18%'],
                    ['label' => 'Lucro', 'class' => 'num', 'width' => '18%'],
                    ['label' => 'Clientes', 'width' => '40%'],
                ],
                'rows' => $vendasPorDia->map(fn (array $linha) => [
                    $linha['data']?->format('d/m/Y') ?? '-',
                    (string) $linha['quantidade'],
                    'R$ '.number_format($linha['total'], 2, ',', '.'),
                    'R$ '.number_format($linha['lucro'], 2, ',', '.'),
                    $linha['clientes']->implode(', '),
                ])->all(),
            ],
        ];

        if ($promissorias->isNotEmpty()) {
            $sections[] = [
                'title' => 'Promissórias do Período',
                'columns' => [
                    ['label' => 'Data', 'width' => '10%'],
                    ['label' => 'Documento', 'width' => '15%'],
                    ['label' => 'Cliente', 'width' => '23%'],
                    ['label' => 'Entrada', 'class' => 'num', 'width' => '12%'],
                    ['label' => 'Financiado', 'class' => 'num', 'width' => '12%'],
                    ['label' => 'Multa', 'class' => 'num', 'width' => '9%'],
                    ['label' => 'Juros/dia', 'class' => 'num', 'width' => '9%'],
                    ['label' => 'Status', 'width' => '10%'],
                ],
                'rows' => $promissorias->map(fn (Promissoria $promissoria) => [
                    optional($promissoria->venda?->data_venda)->format('d/m/Y') ?? '-',
                    $promissoria->numero_documento,
                    $promissoria->cliente?->nome ?? '-',
                    'R$ '.number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                    'R$ '.number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                    number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.').'%',
                    number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.').'%/dia',
                    ucfirst((string) $promissoria->status),
                ])->all(),
            ];
        }

        return app(PdfExporter::class)->download(
            sprintf('relatorio_faturamento_%s_%s.pdf', $dataInicio->toDateString(), $dataFim->toDateString()),
            'Relatório de Faturamento',
            sprintf('Período de %s até %s', $dataInicio->format('d/m/Y'), $dataFim->format('d/m/Y')),
            [
                ['Faturamento total', 'R$ '.number_format($resumo['total_faturamento'], 2, ',', '.')],
                ['Lucro total', 'R$ '.number_format($resumo['total_lucro'], 2, ',', '.')],
                ['Quantidade de vendas', (string) $resumo['quantidade_vendas']],
                ['Vendas com promissória', (string) $resumo['quantidade_promissorias']],
                ['Total financiado', 'R$ '.number_format($resumo['total_financiado_promissorias'], 2, ',', '.')],
                ['Total de entradas', 'R$ '.number_format($resumo['total_entrada_promissorias'], 2, ',', '.')],
            ],
            $sections,
            $empresa
        );
    }

    private function empresaId(Request $request): ?int
    {
        return $request->user()?->usuarioVendas?->empresa_id;
    }
}
