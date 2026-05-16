<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Support\Billing\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $empresaId = $request->user()?->usuarioVendas?->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        $billingService = app(BillingService::class);
        $assinatura = $billingService->currentSubscriptionByEmpresaId((int) $empresaId);
        $permiteXlsx = $billingService->featureEnabled($assinatura, 'permite_exportacao_xlsx');
        $permitePdf = $billingService->featureEnabled($assinatura, 'permite_relatorios_pdf');

        $hoje = Carbon::today();
        $dataInicio = $request->string('data_inicio')->toString() ?: $hoje->copy()->subDays(30)->toDateString();
        $dataFim = $request->string('data_fim')->toString() ?: $hoje->toDateString();

        $filtros = [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];

        return view('relatorios.index', [
            'empresa' => Empresa::query()->find($empresaId),
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'presets' => [
                ['label' => 'Hoje', 'params' => ['data_inicio' => $hoje->toDateString(), 'data_fim' => $hoje->toDateString()]],
                ['label' => '7 dias', 'params' => ['data_inicio' => $hoje->copy()->subDays(6)->toDateString(), 'data_fim' => $hoje->toDateString()]],
                ['label' => '30 dias', 'params' => ['data_inicio' => $hoje->copy()->subDays(29)->toDateString(), 'data_fim' => $hoje->toDateString()]],
                ['label' => 'Mês atual', 'params' => ['data_inicio' => $hoje->copy()->startOfMonth()->toDateString(), 'data_fim' => $hoje->toDateString()]],
            ],
            'reports' => [
                [
                    'title' => 'Faturamento',
                    'description' => 'Consolida vendas confirmadas e concluídas, lucro do período e promissórias emitidas.',
                    'accent' => 'border-emerald-200 bg-emerald-50/70',
                    'openUrl' => route('relatorios.faturamento', $filtros),
                    'csvUrl' => route('relatorios.faturamento', [...$filtros, 'export' => 'csv']),
                    'xlsxUrl' => $permiteXlsx ? route('relatorios.faturamento', [...$filtros, 'export' => 'xlsx']) : null,
                    'pdfUrl' => $permitePdf ? route('relatorios.faturamento', [...$filtros, 'export' => 'pdf']) : null,
                ],
                [
                    'title' => 'Inadimplentes',
                    'description' => 'Agrupa clientes em atraso, títulos vencidos e taxas aplicadas em promissórias abertas.',
                    'accent' => 'border-amber-200 bg-amber-50/70',
                    'openUrl' => route('relatorios.inadimplentes'),
                    'csvUrl' => route('relatorios.inadimplentes', ['export' => 'csv']),
                    'xlsxUrl' => $permiteXlsx ? route('relatorios.inadimplentes', ['export' => 'xlsx']) : null,
                    'pdfUrl' => $permitePdf ? route('relatorios.inadimplentes', ['export' => 'pdf']) : null,
                ],
                [
                    'title' => 'Lucro',
                    'description' => 'Mostra lucro total do período e detalha a contribuição de cada produto para o resultado.',
                    'accent' => 'border-sky-200 bg-sky-50/70',
                    'openUrl' => route('relatorios.lucro', $filtros),
                    'csvUrl' => route('relatorios.lucro', [...$filtros, 'export' => 'csv']),
                    'xlsxUrl' => $permiteXlsx ? route('relatorios.lucro', [...$filtros, 'export' => 'xlsx']) : null,
                    'pdfUrl' => $permitePdf ? route('relatorios.lucro', [...$filtros, 'export' => 'pdf']) : null,
                ],
            ],
        ]);
    }
}