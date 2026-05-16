<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\ContaReceber;
use App\Models\Empresa;
use App\Support\Billing\BillingService;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InadimplentesController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        $billingService = app(BillingService::class);
        $assinatura = $billingService->currentSubscriptionByEmpresaId($empresaId);
        $permiteXlsx = $billingService->featureEnabled($assinatura, 'permite_exportacao_xlsx');
        $permitePdf = $billingService->featureEnabled($assinatura, 'permite_relatorios_pdf');

        $hoje = Carbon::today();

        $titulos = ContaReceber::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', ['vencida', 'aberta', 'parcial'])
            ->whereDate('data_vencimento', '<', $hoje)
            ->with(['cliente', 'venda', 'promissoria'])
            ->orderBy('data_vencimento')
            ->orderBy('cliente_id')
            ->orderBy('id')
            ->get();

        $titulos->each(function (ContaReceber $titulo): void {
            if ($titulo->promissoria && $titulo->promissoria->status !== 'quitada') {
                $titulo->promissoria->sincronizar();
                $titulo->refresh()->load(['cliente', 'venda', 'promissoria']);
            }
        });

        $titulosInadimplentes = $titulos
            ->filter(fn (ContaReceber $titulo) => in_array($titulo->status, ['vencida', 'aberta', 'parcial'], true))
            ->filter(fn (ContaReceber $titulo) => $titulo->data_vencimento && $titulo->data_vencimento->lt($hoje))
            ->filter(fn (ContaReceber $titulo) => $titulo->saldo_devedor > 0)
            ->values();

        $clientes = $titulosInadimplentes
            ->groupBy('cliente_id')
            ->map(function (Collection $titulosCliente) {
                $cliente = $titulosCliente->first()->cliente;

                return [
                    'nome' => $cliente?->nome,
                    'email' => $cliente?->email,
                    'telefone' => $cliente?->telefone,
                    'total_devido' => (float) $titulosCliente->sum(fn (ContaReceber $titulo) => (float) $titulo->saldo_devedor),
                    'quantidade_titulos' => $titulosCliente->count(),
                ];
            })
            ->sortByDesc('total_devido')
            ->values();

        $resumo = [
            'total_clientes' => $clientes->count(),
            'total_titulos' => $titulosInadimplentes->count(),
            'total_geral_devido' => (float) $titulosInadimplentes->sum(fn (ContaReceber $titulo) => (float) $titulo->saldo_devedor),
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportarCsv($clientes, $titulosInadimplentes, $resumo);
        }

        if ($request->query('export') === 'xlsx') {
            if (! $permiteXlsx) {
                return $billingService->deniedFeatureResponse($request, 'Seu plano atual não permite exportação em XLSX.');
            }

            return $this->exportarXlsx($clientes, $titulosInadimplentes, $resumo, $hoje);
        }

        $empresa = Empresa::query()->find($empresaId);

        if ($request->query('export') === 'pdf') {
            if (! $permitePdf) {
                return $billingService->deniedFeatureResponse($request, 'Seu plano atual não permite exportação em PDF.');
            }

            return $this->exportarPdf($empresa, $clientes, $titulosInadimplentes, $resumo, $hoje);
        }

        return view('relatorios.inadimplentes', [
            'empresa' => $empresa,
            'clientes' => $clientes,
            'titulosInadimplentes' => $titulosInadimplentes,
            'resumo' => $resumo,
            'hoje' => $hoje,
            'hubUrl' => route('relatorios.index'),
            'exportXlsxUrl' => $permiteXlsx ? route('relatorios.inadimplentes', ['export' => 'xlsx']) : null,
            'exportPdfUrl' => $permitePdf ? route('relatorios.inadimplentes', ['export' => 'pdf']) : null,
            'exportCsvUrl' => route('relatorios.inadimplentes', ['export' => 'csv']),
        ]);
    }

    private function exportarCsv(Collection $clientes, Collection $titulosInadimplentes, array $resumo)
    {
        return response()->streamDownload(function () use ($clientes, $titulosInadimplentes, $resumo) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Relatório de Inadimplentes'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Total de clientes inadimplentes', $resumo['total_clientes']], ';');
            fputcsv($saida, ['Quantidade de títulos', $resumo['total_titulos']], ';');
            fputcsv($saida, ['Total geral devido', number_format($resumo['total_geral_devido'], 2, ',', '.')], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['Resumo por Cliente'], ';');
            fputcsv($saida, ['Cliente', 'Email', 'Telefone', 'Títulos', 'Total Devido'], ';');
            foreach ($clientes as $cliente) {
                fputcsv($saida, [
                    $cliente['nome'],
                    $cliente['email'],
                    $cliente['telefone'],
                    $cliente['quantidade_titulos'],
                    number_format($cliente['total_devido'], 2, ',', '.'),
                ], ';');
            }

            if ($titulosInadimplentes->isNotEmpty()) {
                fputcsv($saida, [], ';');
                fputcsv($saida, ['Títulos em Atraso'], ';');
                fputcsv($saida, ['Documento', 'Cliente', 'Venda', 'Vencimento', 'Dias em Atraso', 'Saldo', 'Multa (%)', 'Juros (%/dia)', 'Status'], ';');
                foreach ($titulosInadimplentes as $titulo) {
                    $promissoria = $titulo->promissoria;

                    fputcsv($saida, [
                        $promissoria?->numero_documento ?? sprintf('CR-%06d', $titulo->id),
                        $titulo->cliente?->nome,
                        $titulo->venda?->numero,
                        optional($titulo->data_vencimento)->format('d/m/Y') ?? '',
                        $titulo->data_vencimento ? $titulo->data_vencimento->diffInDays(Carbon::today()) : 0,
                        number_format((float) $titulo->saldo_devedor, 2, ',', '.'),
                        $promissoria ? number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') : '',
                        $promissoria ? number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') : '',
                        ucfirst((string) $titulo->status),
                    ], ';');
                }
            }

            fclose($saida);
        }, 'relatorio_inadimplentes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Collection $clientes, Collection $titulosInadimplentes, array $resumo, Carbon $hoje)
    {
        $sections = [[
            'title' => 'Resumo por Cliente',
            'sheet_title' => 'Clientes',
            'columns' => [
                ['label' => 'Cliente'],
                ['label' => 'Email'],
                ['label' => 'Telefone'],
                ['label' => 'Títulos'],
                ['label' => 'Total Devido'],
            ],
            'rows' => $clientes->map(fn (array $cliente) => [
                $cliente['nome'] ?? '-',
                $cliente['email'] ?: '-',
                $cliente['telefone'] ?: '-',
                (string) $cliente['quantidade_titulos'],
                number_format($cliente['total_devido'], 2, ',', '.'),
            ])->all(),
        ]];

        if ($titulosInadimplentes->isNotEmpty()) {
            $sections[] = [
                'title' => 'Títulos em Atraso',
                'sheet_title' => 'Titulos',
                'columns' => [
                    ['label' => 'Documento'],
                    ['label' => 'Cliente'],
                    ['label' => 'Venda'],
                    ['label' => 'Vencimento'],
                    ['label' => 'Dias em Atraso'],
                    ['label' => 'Saldo'],
                    ['label' => 'Multa (%)'],
                    ['label' => 'Juros (%/dia)'],
                    ['label' => 'Status'],
                ],
                'rows' => $titulosInadimplentes->map(function (ContaReceber $titulo) use ($hoje) {
                    $promissoria = $titulo->promissoria;

                    return [
                        $promissoria?->numero_documento ?? sprintf('CR-%06d', $titulo->id),
                        $titulo->cliente?->nome ?? '-',
                        (string) ($titulo->venda?->numero ?? '-'),
                        optional($titulo->data_vencimento)->format('d/m/Y') ?? '-',
                        $titulo->data_vencimento ? (string) $titulo->data_vencimento->diffInDays($hoje) : '0',
                        number_format((float) $titulo->saldo_devedor, 2, ',', '.'),
                        $promissoria ? number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.') : '-',
                        $promissoria ? number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.') : '-',
                        ucfirst((string) $titulo->status),
                    ];
                })->all(),
            ];
        }

        return app(XlsxExporter::class)->download(
            'relatorio_inadimplentes.xlsx',
            'Relatório de Inadimplentes',
            [
                ['Clientes inadimplentes', (string) $resumo['total_clientes']],
                ['Títulos em atraso', (string) $resumo['total_titulos']],
                ['Total geral devido', number_format($resumo['total_geral_devido'], 2, ',', '.')],
                ['Data-base', $hoje->format('d/m/Y')],
            ],
            $sections
        );
    }

    private function exportarPdf(?Empresa $empresa, Collection $clientes, Collection $titulosInadimplentes, array $resumo, Carbon $hoje)
    {
        $sections = [
            [
                'title' => 'Resumo por Cliente',
                'columns' => [
                    ['label' => 'Cliente', 'width' => '26%'],
                    ['label' => 'Email', 'width' => '26%'],
                    ['label' => 'Telefone', 'width' => '16%'],
                    ['label' => 'Títulos', 'class' => 'num', 'width' => '10%'],
                    ['label' => 'Total Devido', 'class' => 'num', 'width' => '22%'],
                ],
                'rows' => $clientes->map(fn (array $cliente) => [
                    $cliente['nome'] ?? '-',
                    $cliente['email'] ?: '-',
                    $cliente['telefone'] ?: '-',
                    (string) $cliente['quantidade_titulos'],
                    'R$ '.number_format($cliente['total_devido'], 2, ',', '.'),
                ])->all(),
            ],
        ];

        if ($titulosInadimplentes->isNotEmpty()) {
            $sections[] = [
                'title' => 'Títulos em Atraso',
                'columns' => [
                    ['label' => 'Documento', 'width' => '13%'],
                    ['label' => 'Cliente', 'width' => '18%'],
                    ['label' => 'Venda', 'class' => 'num', 'width' => '8%'],
                    ['label' => 'Vencimento', 'width' => '10%'],
                    ['label' => 'Dias', 'class' => 'num', 'width' => '8%'],
                    ['label' => 'Saldo', 'class' => 'num', 'width' => '13%'],
                    ['label' => 'Multa', 'class' => 'num', 'width' => '8%'],
                    ['label' => 'Juros/dia', 'class' => 'num', 'width' => '10%'],
                    ['label' => 'Status', 'width' => '12%'],
                ],
                'rows' => $titulosInadimplentes->map(function (ContaReceber $titulo) use ($hoje) {
                    $promissoria = $titulo->promissoria;

                    return [
                        $promissoria?->numero_documento ?? sprintf('CR-%06d', $titulo->id),
                        $titulo->cliente?->nome ?? '-',
                        (string) ($titulo->venda?->numero ?? '-'),
                        optional($titulo->data_vencimento)->format('d/m/Y') ?? '-',
                        $titulo->data_vencimento ? (string) $titulo->data_vencimento->diffInDays($hoje) : '0',
                        'R$ '.number_format((float) $titulo->saldo_devedor, 2, ',', '.'),
                        $promissoria ? number_format((float) $promissoria->percentual_multa_atraso, 2, ',', '.').'%' : '-',
                        $promissoria ? number_format((float) $promissoria->percentual_juros_dia, 4, ',', '.').'%/dia' : '-',
                        ucfirst((string) $titulo->status),
                    ];
                })->all(),
            ];
        }

        return app(PdfExporter::class)->download(
            'relatorio_inadimplentes.pdf',
            'Relatório de Inadimplentes',
            'Títulos vencidos com saldo em aberto na data de geração do relatório',
            [
                ['Clientes inadimplentes', (string) $resumo['total_clientes']],
                ['Títulos em atraso', (string) $resumo['total_titulos']],
                ['Total geral devido', 'R$ '.number_format($resumo['total_geral_devido'], 2, ',', '.')],
                ['Data-base', $hoje->format('d/m/Y')],
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