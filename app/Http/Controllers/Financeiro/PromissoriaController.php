<?php

namespace App\Http\Controllers\Financeiro;

use App\Models\Empresa;
use App\Models\Promissoria;
use App\Http\Controllers\Controller;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class PromissoriaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $statusFiltro = (string) $request->input('status', '');

        $query = Promissoria::query()
            ->with(['cliente', 'venda', 'parcelas'])
            ->where('empresa_id', $empresaId)
            ->orderByDesc('data_emissao');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if (in_array($request->query('export'), ['csv', 'xlsx', 'pdf'], true)) {
            $promissorias = (clone $query)->get();
            $resumo = $this->montarResumoExportacao($promissorias, $statusFiltro);

            return match ($request->query('export')) {
                'csv' => $this->exportarCsv($promissorias, $resumo),
                'xlsx' => $this->exportarXlsx($promissorias, $resumo),
                'pdf' => $this->exportarPdf(Empresa::query()->find($empresaId), $promissorias, $resumo),
            };
        }

        $promissorias = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($promissorias);
        }

        return view('financeiro.promissorias.index', [
            'promissorias' => $promissorias,
            'filtros' => [
                'status' => $statusFiltro,
            ],
            'exportCsvUrl' => route('promissorias.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'csv',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportXlsxUrl' => route('promissorias.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'xlsx',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportPdfUrl' => route('promissorias.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'pdf',
            ], fn ($valor) => $valor !== null && $valor !== '')),
        ]);
    }

    public function show(Request $request, Promissoria $promissoria)
    {
        abort_unless((int) $promissoria->empresa_id === $this->empresaId($request), Response::HTTP_FORBIDDEN, 'Promissória fora do escopo da empresa.');

        $promissoria->load(['cliente', 'venda', 'conta', 'parcelas.pagamentos']);

        if ($request->expectsJson()) {
            return response()->json($promissoria);
        }

        return view('financeiro.promissorias.show', [
            'promissoria' => $promissoria,
        ]);
    }

    private function empresaId(Request $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        return (int) $empresaId;
    }

    private function montarResumoExportacao(Collection $promissorias, string $statusFiltro): array
    {
        return [
            'status' => $statusFiltro !== '' ? ucfirst($statusFiltro) : 'Todos',
            'quantidade' => $promissorias->count(),
            'total_financiado' => (float) $promissorias->sum(fn (Promissoria $promissoria) => (float) $promissoria->valor_financiado),
            'total_entrada' => (float) $promissorias->sum(fn (Promissoria $promissoria) => (float) $promissoria->valor_entrada),
            'saldo_total' => (float) $promissorias->sum(fn (Promissoria $promissoria) => (float) $promissoria->saldo_devedor),
            'quitadas' => $promissorias->where('status', 'quitada')->count(),
            'vencidas' => $promissorias->where('status', 'vencida')->count(),
            'parcelas' => $promissorias->sum(fn (Promissoria $promissoria) => $promissoria->parcelas->count()),
        ];
    }

    private function exportarCsv(Collection $promissorias, array $resumo)
    {
        $fileName = 'promissorias_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.csv';

        return response()->streamDownload(function () use ($promissorias, $resumo) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Listagem de Promissórias'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Status aplicado', $resumo['status']], ';');
            fputcsv($saida, ['Quantidade', $resumo['quantidade']], ';');
            fputcsv($saida, ['Valor financiado', number_format($resumo['total_financiado'], 2, ',', '.')], ';');
            fputcsv($saida, ['Valor de entrada', number_format($resumo['total_entrada'], 2, ',', '.')], ';');
            fputcsv($saida, ['Saldo total', number_format($resumo['saldo_total'], 2, ',', '.')], ';');
            fputcsv($saida, ['Quitadas', $resumo['quitadas']], ';');
            fputcsv($saida, ['Vencidas', $resumo['vencidas']], ';');
            fputcsv($saida, ['Parcelas', $resumo['parcelas']], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['ID', 'Documento', 'Cliente', 'Status', 'Entrada', 'Financiado', 'Saldo', 'Parcelas', 'Venda'], ';');

            foreach ($promissorias as $promissoria) {
                fputcsv($saida, [
                    $promissoria->id,
                    $promissoria->numero_documento,
                    $promissoria->cliente?->nome,
                    ucfirst((string) $promissoria->status),
                    number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                    number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                    number_format((float) $promissoria->saldo_devedor, 2, ',', '.'),
                    $promissoria->parcelas->count(),
                    $promissoria->venda_numero ?: '-',
                ], ';');
            }

            fclose($saida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Collection $promissorias, array $resumo)
    {
        return app(XlsxExporter::class)->download(
            'promissorias_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.xlsx',
            'Listagem de Promissórias',
            [
                ['Status aplicado', $resumo['status']],
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor financiado', number_format($resumo['total_financiado'], 2, ',', '.')],
                ['Valor de entrada', number_format($resumo['total_entrada'], 2, ',', '.')],
                ['Saldo total', number_format($resumo['saldo_total'], 2, ',', '.')],
                ['Quitadas', (string) $resumo['quitadas']],
                ['Vencidas', (string) $resumo['vencidas']],
                ['Parcelas', (string) $resumo['parcelas']],
            ],
            [[
                'title' => 'Promissórias',
                'sheet_title' => 'Promissorias',
                'columns' => [
                    ['label' => 'ID'],
                    ['label' => 'Documento'],
                    ['label' => 'Cliente'],
                    ['label' => 'Status'],
                    ['label' => 'Entrada'],
                    ['label' => 'Financiado'],
                    ['label' => 'Saldo'],
                    ['label' => 'Parcelas'],
                    ['label' => 'Venda'],
                ],
                'rows' => $promissorias->map(fn (Promissoria $promissoria) => [
                    (string) $promissoria->id,
                    $promissoria->numero_documento,
                    $promissoria->cliente?->nome ?? '-',
                    ucfirst((string) $promissoria->status),
                    number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                    number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                    number_format((float) $promissoria->saldo_devedor, 2, ',', '.'),
                    (string) $promissoria->parcelas->count(),
                    $promissoria->venda_numero ? (string) $promissoria->venda_numero : '-',
                ])->all(),
            ]]
        );
    }

    private function exportarPdf(?Empresa $empresa, Collection $promissorias, array $resumo)
    {
        return app(PdfExporter::class)->download(
            'promissorias_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.pdf',
            'Listagem de Promissórias',
            'Status aplicado: '.$resumo['status'],
            [
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor financiado', 'R$ '.number_format($resumo['total_financiado'], 2, ',', '.')],
                ['Valor de entrada', 'R$ '.number_format($resumo['total_entrada'], 2, ',', '.')],
                ['Saldo total', 'R$ '.number_format($resumo['saldo_total'], 2, ',', '.')],
                ['Quitadas', (string) $resumo['quitadas']],
                ['Vencidas', (string) $resumo['vencidas']],
                ['Parcelas', (string) $resumo['parcelas']],
            ],
            [[
                'title' => 'Promissórias filtradas',
                'columns' => [
                    ['label' => 'ID', 'width' => '8%'],
                    ['label' => 'Documento', 'width' => '17%'],
                    ['label' => 'Cliente', 'width' => '22%'],
                    ['label' => 'Status', 'width' => '12%'],
                    ['label' => 'Entrada', 'class' => 'num', 'width' => '11%'],
                    ['label' => 'Financiado', 'class' => 'num', 'width' => '12%'],
                    ['label' => 'Saldo', 'class' => 'num', 'width' => '10%'],
                    ['label' => 'Parcelas', 'class' => 'num', 'width' => '8%'],
                ],
                'rows' => $promissorias->map(fn (Promissoria $promissoria) => [
                    (string) $promissoria->id,
                    $promissoria->numero_documento,
                    $promissoria->cliente?->nome ?? '-',
                    ucfirst((string) $promissoria->status),
                    'R$ '.number_format((float) $promissoria->valor_entrada, 2, ',', '.'),
                    'R$ '.number_format((float) $promissoria->valor_financiado, 2, ',', '.'),
                    'R$ '.number_format((float) $promissoria->saldo_devedor, 2, ',', '.'),
                    (string) $promissoria->parcelas->count(),
                ])->all(),
            ]],
            $empresa
        );
    }
}
