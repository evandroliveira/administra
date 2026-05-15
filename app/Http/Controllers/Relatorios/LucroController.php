<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\ItemVenda;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LucroController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $hoje = Carbon::today();
        $dataInicio = Carbon::parse($request->string('data_inicio')->toString() ?: $hoje->copy()->subDays(30)->toDateString())->startOfDay();
        $dataFim = Carbon::parse($request->string('data_fim')->toString() ?: $hoje->toDateString())->endOfDay();

        $vendas = Venda::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('status', ['confirmada', 'concluida'])
            ->whereBetween('data_venda', [$dataInicio, $dataFim])
            ->get();

        $itens = ItemVenda::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('venda', function ($query) use ($dataInicio, $dataFim) {
                $query->whereIn('status', ['confirmada', 'concluida'])
                    ->whereBetween('data_venda', [$dataInicio, $dataFim]);
            })
            ->with(['produto', 'venda'])
            ->get();

        $totalLucro = (float) $vendas->sum(fn (Venda $venda) => (float) $venda->lucro_total);

        $lucroPorProduto = $itens
            ->groupBy('produto_id')
            ->map(function (Collection $grupo) use ($totalLucro) {
                $primeiroItem = $grupo->first();
                $lucroProduto = (float) $grupo->sum(fn (ItemVenda $item) => (float) $item->lucro);

                return [
                    'produto' => $primeiroItem?->produto?->nome,
                    'quantidade' => (int) $grupo->sum(fn (ItemVenda $item) => (int) $item->quantidade),
                    'total_lucro' => $lucroProduto,
                    'percentual_total' => $totalLucro > 0 ? round(($lucroProduto / $totalLucro) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('total_lucro')
            ->values();

        if ($request->query('export') === 'csv') {
            return $this->exportarCsv($dataInicio, $dataFim, $totalLucro, $lucroPorProduto);
        }

        if ($request->query('export') === 'xlsx') {
            return $this->exportarXlsx($dataInicio, $dataFim, $totalLucro, $lucroPorProduto);
        }

        $empresa = Empresa::query()->find($empresaId);

        if ($request->query('export') === 'pdf') {
            return $this->exportarPdf($empresa, $dataInicio, $dataFim, $totalLucro, $lucroPorProduto);
        }

        return view('relatorios.lucro', [
            'empresa' => $empresa,
            'dataInicio' => $dataInicio->toDateString(),
            'dataFim' => $dataFim->toDateString(),
            'totalLucro' => $totalLucro,
            'lucroPorProduto' => $lucroPorProduto,
            'hubUrl' => route('relatorios.index', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
            ]),
            'exportXlsxUrl' => route('relatorios.lucro', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'xlsx',
            ]),
            'exportPdfUrl' => route('relatorios.lucro', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'pdf',
            ]),
            'exportCsvUrl' => route('relatorios.lucro', [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'export' => 'csv',
            ]),
        ]);
    }

    private function exportarCsv(Carbon $dataInicio, Carbon $dataFim, float $totalLucro, Collection $lucroPorProduto)
    {
        $fileName = sprintf('relatorio_lucro_%s_%s.csv', $dataInicio->toDateString(), $dataFim->toDateString());

        return response()->streamDownload(function () use ($dataInicio, $dataFim, $totalLucro, $lucroPorProduto) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Relatório de Lucro'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Período inicial', $dataInicio->format('d/m/Y')], ';');
            fputcsv($saida, ['Período final', $dataFim->format('d/m/Y')], ';');
            fputcsv($saida, ['Lucro total', number_format($totalLucro, 2, ',', '.')], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['Lucro por Produto'], ';');
            fputcsv($saida, ['Produto', 'Quantidade Vendida', 'Lucro', '% do Total'], ';');
            foreach ($lucroPorProduto as $linha) {
                fputcsv($saida, [
                    $linha['produto'],
                    $linha['quantidade'],
                    number_format($linha['total_lucro'], 2, ',', '.'),
                    number_format($linha['percentual_total'], 2, ',', '.').'%',
                ], ';');
            }

            fclose($saida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Carbon $dataInicio, Carbon $dataFim, float $totalLucro, Collection $lucroPorProduto)
    {
        return app(XlsxExporter::class)->download(
            sprintf('relatorio_lucro_%s_%s.xlsx', $dataInicio->toDateString(), $dataFim->toDateString()),
            'Relatório de Lucro',
            [
                ['Período inicial', $dataInicio->format('d/m/Y')],
                ['Período final', $dataFim->format('d/m/Y')],
                ['Lucro total', number_format($totalLucro, 2, ',', '.')],
                ['Produtos no relatório', (string) $lucroPorProduto->count()],
                ['Quantidade vendida', (string) $lucroPorProduto->sum('quantidade')],
            ],
            [[
                'title' => 'Lucro por Produto',
                'sheet_title' => 'Lucro por Produto',
                'columns' => [
                    ['label' => 'Produto'],
                    ['label' => 'Quantidade Vendida'],
                    ['label' => 'Lucro'],
                    ['label' => '% do Total'],
                ],
                'rows' => $lucroPorProduto->map(fn (array $linha) => [
                    $linha['produto'] ?? '-',
                    (string) $linha['quantidade'],
                    number_format($linha['total_lucro'], 2, ',', '.'),
                    number_format($linha['percentual_total'], 2, ',', '.').'%',
                ])->all(),
            ]]
        );
    }

    private function exportarPdf(?Empresa $empresa, Carbon $dataInicio, Carbon $dataFim, float $totalLucro, Collection $lucroPorProduto)
    {
        return app(PdfExporter::class)->download(
            sprintf('relatorio_lucro_%s_%s.pdf', $dataInicio->toDateString(), $dataFim->toDateString()),
            'Relatório de Lucro',
            sprintf('Período de %s até %s', $dataInicio->format('d/m/Y'), $dataFim->format('d/m/Y')),
            [
                ['Lucro total', 'R$ '.number_format($totalLucro, 2, ',', '.')],
                ['Produtos no relatório', (string) $lucroPorProduto->count()],
                ['Quantidade vendida', (string) $lucroPorProduto->sum('quantidade')],
            ],
            [[
                'title' => 'Lucro por Produto',
                'columns' => [
                    ['label' => 'Produto', 'width' => '44%'],
                    ['label' => 'Quantidade Vendida', 'class' => 'num', 'width' => '18%'],
                    ['label' => 'Lucro', 'class' => 'num', 'width' => '18%'],
                    ['label' => '% do Total', 'class' => 'num', 'width' => '20%'],
                ],
                'rows' => $lucroPorProduto->map(fn (array $linha) => [
                    $linha['produto'] ?? '-',
                    (string) $linha['quantidade'],
                    'R$ '.number_format($linha['total_lucro'], 2, ',', '.'),
                    number_format($linha['percentual_total'], 2, ',', '.').'%',
                ])->all(),
            ]],
            $empresa
        );
    }

    private function empresaId(Request $request): ?int
    {
        return $request->user()?->usuarioVendas?->empresa_id;
    }
}