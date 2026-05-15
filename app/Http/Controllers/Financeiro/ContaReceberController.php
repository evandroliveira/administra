<?php

namespace App\Http\Controllers\Financeiro;

use App\Models\Empresa;
use App\Models\ContaReceber;
use App\Http\Controllers\Controller;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ContaReceberController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $statusFiltro = (string) $request->input('status', '');
        $vencimentoInicio = (string) $request->input('vencimento_inicio', '');
        $vencimentoFim = (string) $request->input('vencimento_fim', '');

        $query = ContaReceber::query()
            ->with(['cliente', 'venda', 'promissoria'])
            ->where('empresa_id', $empresaId)
            ->orderBy('data_vencimento');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('vencimento_inicio')) {
            $query->whereDate('data_vencimento', '>=', $request->string('vencimento_inicio'));
        }

        if ($request->filled('vencimento_fim')) {
            $query->whereDate('data_vencimento', '<=', $request->string('vencimento_fim'));
        }

        if (in_array($request->query('export'), ['csv', 'xlsx', 'pdf'], true)) {
            $contas = (clone $query)->get();
            $resumo = $this->montarResumoExportacao($contas, [
                'status' => $statusFiltro,
                'vencimento_inicio' => $vencimentoInicio,
                'vencimento_fim' => $vencimentoFim,
            ]);

            return match ($request->query('export')) {
                'csv' => $this->exportarCsv($contas, $resumo),
                'xlsx' => $this->exportarXlsx($contas, $resumo),
                'pdf' => $this->exportarPdf(Empresa::query()->find($empresaId), $contas, $resumo),
            };
        }

        $contas = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($contas);
        }

        return view('financeiro.contas_receber.index', [
            'contas' => $contas,
            'filtros' => [
                'status' => $statusFiltro,
                'vencimento_inicio' => $vencimentoInicio,
                'vencimento_fim' => $vencimentoFim,
            ],
            'exportCsvUrl' => route('contas.receber.index', array_filter([
                'status' => $statusFiltro,
                'vencimento_inicio' => $vencimentoInicio,
                'vencimento_fim' => $vencimentoFim,
                'export' => 'csv',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportXlsxUrl' => route('contas.receber.index', array_filter([
                'status' => $statusFiltro,
                'vencimento_inicio' => $vencimentoInicio,
                'vencimento_fim' => $vencimentoFim,
                'export' => 'xlsx',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportPdfUrl' => route('contas.receber.index', array_filter([
                'status' => $statusFiltro,
                'vencimento_inicio' => $vencimentoInicio,
                'vencimento_fim' => $vencimentoFim,
                'export' => 'pdf',
            ], fn ($valor) => $valor !== null && $valor !== '')),
        ]);
    }

    public function show(Request $request, ContaReceber $contaReceber)
    {
        $this->assertEmpresa($request, (int) $contaReceber->empresa_id);

        $contaReceber->load(['cliente', 'venda', 'pagamentos', 'promissoria.parcelas']);

        if ($request->expectsJson()) {
            return response()->json($contaReceber);
        }

        return view('financeiro.contas_receber.show', [
            'conta' => $contaReceber,
        ]);
    }

    private function empresaId(Request $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        return (int) $empresaId;
    }

    private function assertEmpresa(Request $request, int $empresaId): void
    {
        abort_unless($this->empresaId($request) === $empresaId, Response::HTTP_FORBIDDEN, 'Recurso fora do escopo da empresa.');
    }

    private function montarResumoExportacao(Collection $contas, array $filtros): array
    {
        $totalOriginal = (float) $contas->sum(fn (ContaReceber $conta) => (float) $conta->valor_original);
        $totalPago = (float) $contas->sum(fn (ContaReceber $conta) => (float) $conta->valor_pago);
        $saldoTotal = (float) $contas->sum(fn (ContaReceber $conta) => (float) $conta->saldo_devedor);

        return [
            'status' => $filtros['status'] !== '' ? ucfirst((string) $filtros['status']) : 'Todos',
            'vencimento_inicio' => $filtros['vencimento_inicio'] !== '' ? $filtros['vencimento_inicio'] : '-',
            'vencimento_fim' => $filtros['vencimento_fim'] !== '' ? $filtros['vencimento_fim'] : '-',
            'quantidade' => $contas->count(),
            'total_original' => $totalOriginal,
            'total_pago' => $totalPago,
            'saldo_total' => $saldoTotal,
            'quitadas' => $contas->where('status', 'quitada')->count(),
            'vencidas' => $contas->where('status', 'vencida')->count(),
            'promissorias' => $contas->filter(fn (ContaReceber $conta) => $conta->promissoria !== null)->count(),
        ];
    }

    private function exportarCsv(Collection $contas, array $resumo)
    {
        $fileName = 'contas_receber_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.csv';

        return response()->streamDownload(function () use ($contas, $resumo) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Listagem de Contas a Receber'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Status aplicado', $resumo['status']], ';');
            fputcsv($saida, ['Vencimento inicial', $resumo['vencimento_inicio']], ';');
            fputcsv($saida, ['Vencimento final', $resumo['vencimento_fim']], ';');
            fputcsv($saida, ['Quantidade', $resumo['quantidade']], ';');
            fputcsv($saida, ['Valor original', number_format($resumo['total_original'], 2, ',', '.')], ';');
            fputcsv($saida, ['Valor pago', number_format($resumo['total_pago'], 2, ',', '.')], ';');
            fputcsv($saida, ['Saldo total', number_format($resumo['saldo_total'], 2, ',', '.')], ';');
            fputcsv($saida, ['Quitadas', $resumo['quitadas']], ';');
            fputcsv($saida, ['Vencidas', $resumo['vencidas']], ';');
            fputcsv($saida, ['Com promissória', $resumo['promissorias']], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['ID', 'Cliente', 'Venda', 'Vencimento', 'Status', 'Original', 'Pago', 'Saldo', 'Promissória'], ';');

            foreach ($contas as $conta) {
                fputcsv($saida, [
                    $conta->id,
                    $conta->cliente?->nome,
                    $conta->venda_numero ?: '-',
                    optional($conta->data_vencimento)->format('d/m/Y') ?? '-',
                    ucfirst((string) $conta->status),
                    number_format((float) $conta->valor_original, 2, ',', '.'),
                    number_format((float) $conta->valor_pago, 2, ',', '.'),
                    number_format((float) $conta->saldo_devedor, 2, ',', '.'),
                    $conta->promissoria?->numero_documento ?: '-',
                ], ';');
            }

            fclose($saida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Collection $contas, array $resumo)
    {
        return app(XlsxExporter::class)->download(
            'contas_receber_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.xlsx',
            'Listagem de Contas a Receber',
            [
                ['Status aplicado', $resumo['status']],
                ['Vencimento inicial', $resumo['vencimento_inicio']],
                ['Vencimento final', $resumo['vencimento_fim']],
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor original', number_format($resumo['total_original'], 2, ',', '.')],
                ['Valor pago', number_format($resumo['total_pago'], 2, ',', '.')],
                ['Saldo total', number_format($resumo['saldo_total'], 2, ',', '.')],
                ['Quitadas', (string) $resumo['quitadas']],
                ['Vencidas', (string) $resumo['vencidas']],
                ['Com promissória', (string) $resumo['promissorias']],
            ],
            [[
                'title' => 'Contas a Receber',
                'sheet_title' => 'Contas a Receber',
                'columns' => [
                    ['label' => 'ID'],
                    ['label' => 'Cliente'],
                    ['label' => 'Venda'],
                    ['label' => 'Vencimento'],
                    ['label' => 'Status'],
                    ['label' => 'Original'],
                    ['label' => 'Pago'],
                    ['label' => 'Saldo'],
                    ['label' => 'Promissória'],
                ],
                'rows' => $contas->map(fn (ContaReceber $conta) => [
                    (string) $conta->id,
                    $conta->cliente?->nome ?? '-',
                    $conta->venda_numero ? (string) $conta->venda_numero : '-',
                    optional($conta->data_vencimento)->format('d/m/Y') ?? '-',
                    ucfirst((string) $conta->status),
                    number_format((float) $conta->valor_original, 2, ',', '.'),
                    number_format((float) $conta->valor_pago, 2, ',', '.'),
                    number_format((float) $conta->saldo_devedor, 2, ',', '.'),
                    $conta->promissoria?->numero_documento ?: '-',
                ])->all(),
            ]]
        );
    }

    private function exportarPdf(?Empresa $empresa, Collection $contas, array $resumo)
    {
        return app(PdfExporter::class)->download(
            'contas_receber_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.pdf',
            'Listagem de Contas a Receber',
            sprintf('Status: %s | Vencimento: %s até %s', $resumo['status'], $resumo['vencimento_inicio'], $resumo['vencimento_fim']),
            [
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor original', 'R$ '.number_format($resumo['total_original'], 2, ',', '.')],
                ['Valor pago', 'R$ '.number_format($resumo['total_pago'], 2, ',', '.')],
                ['Saldo total', 'R$ '.number_format($resumo['saldo_total'], 2, ',', '.')],
                ['Quitadas', (string) $resumo['quitadas']],
                ['Vencidas', (string) $resumo['vencidas']],
                ['Com promissória', (string) $resumo['promissorias']],
            ],
            [[
                'title' => 'Contas filtradas',
                'columns' => [
                    ['label' => 'ID', 'width' => '8%'],
                    ['label' => 'Cliente', 'width' => '22%'],
                    ['label' => 'Venda', 'width' => '10%'],
                    ['label' => 'Vencimento', 'width' => '14%'],
                    ['label' => 'Status', 'width' => '12%'],
                    ['label' => 'Original', 'class' => 'num', 'width' => '12%'],
                    ['label' => 'Pago', 'class' => 'num', 'width' => '10%'],
                    ['label' => 'Saldo', 'class' => 'num', 'width' => '12%'],
                ],
                'rows' => $contas->map(fn (ContaReceber $conta) => [
                    (string) $conta->id,
                    $conta->cliente?->nome ?? '-',
                    $conta->venda_numero ? (string) $conta->venda_numero : '-',
                    optional($conta->data_vencimento)->format('d/m/Y') ?? '-',
                    ucfirst((string) $conta->status),
                    'R$ '.number_format((float) $conta->valor_original, 2, ',', '.'),
                    'R$ '.number_format((float) $conta->valor_pago, 2, ',', '.'),
                    'R$ '.number_format((float) $conta->saldo_devedor, 2, ',', '.'),
                ])->all(),
            ]],
            $empresa
        );
    }
}
