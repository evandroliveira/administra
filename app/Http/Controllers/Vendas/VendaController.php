<?php

namespace App\Http\Controllers\Vendas;

use App\Http\Requests\Vendas\StoreVendaRequest;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\ItemVenda;
use App\Models\PagamentoReceber;
use App\Models\Promissoria;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\Empresa;
use App\Http\Controllers\Controller;
use App\Support\Fiscal\FiscalConfigurationException;
use App\Support\Fiscal\FiscalEmissionException;
use App\Support\Fiscal\FiscalService;
use App\Support\Relatorios\PdfExporter;
use App\Support\Relatorios\XlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VendaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $fiscalService = app(FiscalService::class);
        $statusFiltro = $request->input('status');

        $query = Venda::query()
            ->with(['cliente', 'vendedor.user', 'itens'])
            ->where('empresa_id', $empresaId)
            ->orderByDesc('data_venda');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if (in_array($request->query('export'), ['csv', 'xlsx', 'pdf'], true)) {
            $vendas = (clone $query)->get();
            $resumo = $this->montarResumoExportacao($vendas, $statusFiltro);

            return match ($request->query('export')) {
                'csv' => $this->exportarCsv($vendas, $resumo),
                'xlsx' => $this->exportarXlsx($vendas, $resumo),
                'pdf' => $this->exportarPdf(Empresa::query()->find($empresaId), $vendas, $resumo),
            };
        }

        $vendas = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($vendas);
        }

        return view('vendas.index', [
            'vendas' => $vendas,
            'filtros' => [
                'status' => (string) $request->input('status', ''),
            ],
            'fiscalHabilitada' => $fiscalService->enabled(),
            'exportCsvUrl' => route('vendas.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'csv',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportXlsxUrl' => route('vendas.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'xlsx',
            ], fn ($valor) => $valor !== null && $valor !== '')),
            'exportPdfUrl' => route('vendas.index', array_filter([
                'status' => $statusFiltro,
                'export' => 'pdf',
            ], fn ($valor) => $valor !== null && $valor !== '')),
        ]);
    }

    public function create(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $fiscalService = app(FiscalService::class);

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $produtos = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('vendas.create', [
            'clientes' => $clientes,
            'produtos' => $produtos,
            'metodosPagamentoReceber' => PagamentoReceber::METODOS,
            'fiscalHabilitada' => $fiscalService->enabled(),
            'fiscalConfigurada' => $fiscalService->configured(),
            'fiscalProviderLabel' => $fiscalService->providerLabel(),
        ]);
    }

    public function store(StoreVendaRequest $request)
    {
        $empresaId = $this->empresaId($request);
        $dados = $request->validated();
        $fiscalService = app(FiscalService::class);

        $cliente = Cliente::query()
            ->whereKey((int) $dados['cliente_id'])
            ->where('empresa_id', $empresaId)
            ->first();

        abort_unless($cliente, Response::HTTP_UNPROCESSABLE_ENTITY, 'Cliente inválido para a empresa.');

        $resultado = DB::transaction(function () use ($dados, $empresaId, $cliente, $request) {
            $venda = new Venda();
            $venda->empresa_id = $empresaId;
            $venda->cliente_id = $cliente->id;
            $venda->vendedor_id = optional($request->user()?->usuarioVendas)->id;
            $venda->status = $dados['status'];
            $venda->desconto = $dados['desconto'];
            $venda->frete = $dados['frete'];
            $venda->percentual_multa_atraso_promissoria = $this->resolverTaxaPromissoria(
                $dados['percentual_multa_atraso'] ?? null,
                (float) $cliente->percentual_multa_atraso_padrao,
                2
            );
            $venda->percentual_juros_dia_promissoria = $this->resolverTaxaPromissoria(
                $dados['percentual_juros_dia'] ?? null,
                (float) $cliente->percentual_juros_dia_padrao,
                0.0333
            );
            $venda->data_entrega = $dados['data_entrega'] ?? null;
            $venda->observacoes = $dados['observacoes'] ?? null;
            $venda->subtotal = 0;
            $venda->total = 0;
            $venda->lucro_total = 0;
            $venda->emitir_nota_fiscal = false;
            $venda->status_nota_fiscal = 'nao_emitir';
            $venda->nota_fiscal_payload = [];
            $venda->save();

            $subtotal = 0.0;
            $lucroTotal = 0.0;

            foreach ($dados['itens'] as $item) {
                $produto = Produto::query()
                    ->whereKey((int) $item['produto_id'])
                    ->where('empresa_id', $empresaId)
                    ->first();

                abort_unless($produto, Response::HTTP_UNPROCESSABLE_ENTITY, 'Produto inválido para a empresa.');

                $quantidade = (int) $item['quantidade'];
                abort_unless($produto->estoque_atual >= $quantidade, Response::HTTP_UNPROCESSABLE_ENTITY, 'Estoque insuficiente para o produto '.$produto->nome.'.');

                $precoUnitario = isset($item['preco_unitario']) ? (float) $item['preco_unitario'] : (float) $produto->preco_venda;
                $valorTotal = $quantidade * $precoUnitario;
                $custoUnitario = (float) $produto->custo_base_estoque;
                $lucro = $valorTotal - ($quantidade * $custoUnitario);

                ItemVenda::create([
                    'empresa_id' => $empresaId,
                    'venda_numero' => $venda->numero,
                    'produto_id' => $produto->id,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'custo_unitario' => $custoUnitario,
                    'valor_total' => $valorTotal,
                    'lucro' => $lucro,
                ]);

                $produto->estoque_atual = (int) $produto->estoque_atual - $quantidade;
                $produto->save();

                $subtotal += $valorTotal;
                $lucroTotal += $lucro;
            }

            $venda->subtotal = $subtotal;
            $venda->total = max($subtotal - (float) $venda->desconto + (float) $venda->frete, 0);
            $venda->lucro_total = $lucroTotal;
            $venda->save();

            $modalidadePagamento = $this->resolverModalidadePagamento($dados);
            $metodoPagamentoAvista = $dados['metodo_pagamento_avista'] ?? 'dinheiro';
            $gerarPromissoria = $modalidadePagamento === 'promissoria';
            $valorEntrada = $modalidadePagamento === 'avista'
                ? round((float) $venda->total, 2)
                : round((float) ($dados['valor_entrada'] ?? 0), 2);
            $promissoria = null;
            $pagamentoAvista = null;

            abort_unless($valorEntrada >= 0, Response::HTTP_UNPROCESSABLE_ENTITY, 'A entrada não pode ser negativa.');
            abort_unless($valorEntrada <= (float) $venda->total, Response::HTTP_UNPROCESSABLE_ENTITY, 'A entrada não pode ser maior que o total da venda.');

            if (! $gerarPromissoria && $modalidadePagamento !== 'avista' && $valorEntrada > 0) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'A entrada só pode ser informada quando a promissória estiver habilitada.');
            }

            if ($modalidadePagamento === 'avista') {
                abort_unless(
                    array_key_exists($metodoPagamentoAvista, PagamentoReceber::METODOS),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'Selecione um método de pagamento válido para a venda à vista.'
                );

                $conta = ContaReceber::create([
                    'empresa_id' => $empresaId,
                    'venda_numero' => $venda->numero,
                    'cliente_id' => $cliente->id,
                    'valor_original' => $venda->total,
                    'valor_pago' => 0,
                    'valor_juros' => 0,
                    'data_vencimento' => Carbon::today()->toDateString(),
                    'status' => 'aberta',
                    'observacoes' => 'Pagamento à vista registrado automaticamente para a venda #'.$venda->numero,
                ]);

                $pagamentoAvista = PagamentoReceber::create([
                    'empresa_id' => $empresaId,
                    'conta_id' => $conta->id,
                    'valor' => $venda->total,
                    'valor_abatimento' => 0,
                    'valor_multa' => 0,
                    'valor_juros' => 0,
                    'metodo' => $metodoPagamentoAvista,
                    'observacoes' => 'Pagamento à vista registrado no fechamento da venda.',
                ]);
            } elseif ($gerarPromissoria) {
                $saldoFinanciado = round((float) $venda->total - $valorEntrada, 2);
                abort_unless($saldoFinanciado > 0, Response::HTTP_UNPROCESSABLE_ENTITY, 'A promissória exige saldo financiado maior que zero.');

                $creditoDisponivel = $cliente->recalcularCreditoDisponivel();
                abort_unless(
                    $saldoFinanciado <= $creditoDisponivel,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'Crédito disponível insuficiente para este cliente. Disponível no momento: R$ '.number_format($creditoDisponivel, 2, ',', '.').'.'
                );

                $primeiraParcela = $dados['data_primeira_parcela']
                    ?? $dados['data_vencimento']
                    ?? Carbon::today()->addDays(30)->toDateString();

                $conta = ContaReceber::create([
                    'empresa_id' => $empresaId,
                    'venda_numero' => $venda->numero,
                    'cliente_id' => $cliente->id,
                    'valor_original' => $saldoFinanciado,
                    'valor_pago' => 0,
                    'valor_juros' => 0,
                    'data_vencimento' => $primeiraParcela,
                    'status' => 'aberta',
                    'observacoes' => 'Promissória gerada automaticamente pela venda #'.$venda->numero,
                ]);

                $promissoria = Promissoria::create([
                    'empresa_id' => $empresaId,
                    'conta_id' => $conta->id,
                    'venda_numero' => $venda->numero,
                    'cliente_id' => $cliente->id,
                    'valor_entrada' => $valorEntrada,
                    'valor_financiado' => $saldoFinanciado,
                    'quantidade_parcelas' => (int) ($dados['quantidade_parcelas'] ?? 1),
                    'intervalo_dias' => (int) ($dados['intervalo_dias'] ?? 30),
                    'primeira_parcela_vencimento' => $primeiraParcela,
                    'percentual_multa_atraso' => (float) $venda->percentual_multa_atraso_promissoria,
                    'percentual_juros_dia' => (float) $venda->percentual_juros_dia_promissoria,
                    'observacoes' => $dados['observacoes_promissoria'] ?? null,
                ]);

                $promissoria->gerarParcelas();
            } else {
                ContaReceber::create([
                    'empresa_id' => $empresaId,
                    'venda_numero' => $venda->numero,
                    'cliente_id' => $cliente->id,
                    'valor_original' => $venda->total,
                    'valor_pago' => 0,
                    'valor_juros' => 0,
                    'data_vencimento' => $dados['data_vencimento'] ?? Carbon::today()->addDays(30)->toDateString(),
                    'status' => 'aberta',
                    'observacoes' => 'Gerada automaticamente pela venda #'.$venda->numero,
                ]);
            }

            return [
                'venda' => $venda,
                'modalidade_pagamento' => $modalidadePagamento,
                'pagamento_avista_id' => $pagamentoAvista?->id,
                'promissoria_id' => $promissoria?->id,
            ];
        });

        $venda = $resultado['venda'];

        $venda->load(['cliente', 'itens.produto', 'contaReceber', 'promissoria.parcelas']);

        $mensagemFiscal = null;
        if (! empty($dados['emitir_nota_fiscal'])) {
            $mensagemFiscal = $this->processarNotaFiscalAutomaticamente($fiscalService, $venda);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Venda registrada com sucesso.',
                'venda' => $venda,
            ], Response::HTTP_CREATED);
        }

        if ($resultado['modalidade_pagamento'] === 'promissoria' && $resultado['promissoria_id']) {
            return redirect()
                ->route('vendas.promissoria.imprimir', ['venda' => $venda, 'auto_print' => 1])
                ->with('status', trim('Venda finalizada. A promissória foi gerada para impressão.'.($mensagemFiscal ? ' '.$mensagemFiscal : '')));
        }

        if ($resultado['modalidade_pagamento'] === 'avista' && $resultado['pagamento_avista_id']) {
            return redirect()
                ->route('vendas.recibo', ['venda' => $venda, 'auto_print' => 1])
                ->with('status', trim('Venda finalizada, quitada à vista e recibo gerado para impressão.'.($mensagemFiscal ? ' '.$mensagemFiscal : '')));
        }

        return redirect()
            ->route('vendas.show', $venda)
            ->with('status', trim('Venda registrada com sucesso.'.($mensagemFiscal ? ' '.$mensagemFiscal : '')));
    }

    public function show(Request $request, Venda $venda)
    {
        $this->assertEmpresa($request, (int) $venda->empresa_id);

        $venda->load(['cliente', 'vendedor.user', 'itens.produto', 'contaReceber.pagamentos', 'promissoria.parcelas']);

        if ($request->expectsJson()) {
            return response()->json($venda);
        }

        return view('vendas.show', [
            'venda' => $venda,
            'fiscalHabilitada' => app(FiscalService::class)->enabled(),
        ]);
    }

    public function receipt(Request $request, Venda $venda)
    {
        $this->assertEmpresa($request, (int) $venda->empresa_id);

        $venda->load(['cliente', 'vendedor.user', 'itens.produto', 'contaReceber.pagamentos', 'empresa']);
        $pagamento = $venda->contaReceber?->pagamentos?->sortByDesc('data_pagamento')->first();

        abort_unless($pagamento, Response::HTTP_NOT_FOUND, 'Recibo indisponível para esta venda.');

        return view('vendas.receipt', [
            'venda' => $venda,
            'pagamento' => $pagamento,
            'empresa' => Empresa::query()->find($venda->empresa_id),
            'autoPrint' => $request->boolean('auto_print'),
        ]);
    }

    public function printPromissoria(Request $request, Venda $venda)
    {
        $this->assertEmpresa($request, (int) $venda->empresa_id);

        $venda->load(['cliente', 'vendedor.user', 'itens.produto', 'promissoria.parcelas', 'empresa']);
        $promissoria = $venda->promissoria;

        abort_unless($promissoria, Response::HTTP_NOT_FOUND, 'Promissória indisponível para esta venda.');
        $promissoria->sincronizar();
        $promissoria->refresh()->load(['cliente', 'venda.itens.produto', 'parcelas']);

        return view('vendas.promissoria_print', [
            'venda' => $venda,
            'promissoria' => $promissoria,
            'empresa' => Empresa::query()->find($venda->empresa_id),
            'autoPrint' => $request->boolean('auto_print', true),
        ]);
    }

    public function emitirNotaFiscal(Request $request, Venda $venda)
    {
        $this->assertEmpresa($request, (int) $venda->empresa_id);

        if (! $venda->podeEmitirNotaFiscal()) {
            return redirect($this->destinoVenda($request, $venda))
                ->with('status', 'A nota fiscal pode ser emitida apenas para vendas confirmadas ou concluídas que ainda não tenham nota emitida.');
        }

        $fiscalService = app(FiscalService::class);

        try {
            $fiscalService->requestEmission($venda);

            $mensagem = match ($venda->fresh()->status_nota_fiscal) {
                'emitida' => 'Nota fiscal da venda #'.$venda->numero.' emitida com sucesso.',
                'pendente' => $venda->fresh()->nota_fiscal_mensagem ?: 'Solicitação de emissão enviada com sucesso.',
                'nao_emitir' => $venda->fresh()->nota_fiscal_mensagem ?: 'A emissão fiscal está desabilitada no momento.',
                default => $venda->fresh()->nota_fiscal_mensagem ?: 'A emissão da nota fiscal não foi concluída.',
            };
        } catch (FiscalConfigurationException|FiscalEmissionException $exception) {
            $fiscalService->markFailure($venda, $exception->getMessage());
            $mensagem = 'Não foi possível emitir a nota fiscal: '.$exception->getMessage();
        }

        return redirect($this->destinoVenda($request, $venda))->with('status', $mensagem);
    }

    public function updateStatus(Request $request, Venda $venda)
    {
        $this->assertEmpresa($request, (int) $venda->empresa_id);

        $dados = $request->validate([
            'status' => ['required', 'in:pendente,confirmada,concluida,cancelada'],
        ]);

        $venda->status = $dados['status'];
        $venda->save();

        $mensagemFiscal = $this->processarNotaFiscalPorMudancaDeStatus(app(FiscalService::class), $venda);
        $mensagem = 'Status da venda atualizado para '.ucfirst($venda->status).'.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => trim($mensagem.($mensagemFiscal ? ' '.$mensagemFiscal : '')),
                'venda' => $venda->fresh(['cliente', 'itens.produto', 'contaReceber', 'promissoria.parcelas']),
            ]);
        }

        return redirect($this->destinoVenda($request, $venda))
            ->with('status', trim($mensagem.($mensagemFiscal ? ' '.$mensagemFiscal : '')));
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

    private function resolverModalidadePagamento(array $dados): string
    {
        $modalidadePagamento = $dados['modalidade_pagamento'] ?? null;

        if ($modalidadePagamento) {
            return $modalidadePagamento;
        }

        return ! empty($dados['gerar_promissoria']) ? 'promissoria' : 'conta';
    }

    private function processarNotaFiscalAutomaticamente(FiscalService $fiscalService, Venda $venda): ?string
    {
        if (! $fiscalService->enabled()) {
            $fiscalService->requestEmission($venda);
        } elseif ($venda->podeEmitirNotaFiscal()) {
            try {
                $fiscalService->requestEmission($venda);
            } catch (FiscalConfigurationException|FiscalEmissionException $exception) {
                $fiscalService->markFailure($venda, $exception->getMessage());
            }
        } else {
            $fiscalService->markPending($venda, 'A nota fiscal será emitida quando a venda estiver confirmada ou concluída.');
        }

        $venda->refresh();

        return match ($venda->status_nota_fiscal) {
            'emitida' => 'Nota fiscal emitida com sucesso.',
            'pendente' => $venda->nota_fiscal_mensagem ?: 'Emissão fiscal pendente.',
            'erro' => $venda->nota_fiscal_mensagem ?: 'Falha ao emitir a nota fiscal.',
            default => null,
        };
    }

    private function processarNotaFiscalPorMudancaDeStatus(FiscalService $fiscalService, Venda $venda): ?string
    {
        if (! $venda->emitir_nota_fiscal || ! $venda->podeEmitirNotaFiscal()) {
            return null;
        }

        try {
            $fiscalService->requestEmission($venda);
        } catch (FiscalConfigurationException|FiscalEmissionException $exception) {
            $fiscalService->markFailure($venda, $exception->getMessage());
        }

        $venda->refresh();

        return match ($venda->status_nota_fiscal) {
            'emitida' => 'Nota fiscal emitida automaticamente após a atualização do status.',
            'pendente' => $venda->nota_fiscal_mensagem ?: 'A emissão fiscal segue pendente.',
            'erro' => $venda->nota_fiscal_mensagem ?: 'Falha ao emitir a nota fiscal após a atualização do status.',
            'nao_emitir' => $venda->nota_fiscal_mensagem ?: 'A emissão fiscal está desabilitada no momento.',
            default => null,
        };
    }

    private function destinoVenda(Request $request, Venda $venda): string
    {
        $next = (string) $request->input('next', '');

        if ($next !== '' && str_starts_with($next, '/')) {
            return $next;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($next !== '' && $appUrl !== '' && str_starts_with($next, $appUrl)) {
            return $next;
        }

        return route('vendas.show', $venda);
    }

    private function resolverTaxaPromissoria(mixed $valorInformado, float $valorPadrao, float $fallback): float
    {
        if ($valorInformado === null || $valorInformado === '') {
            return $valorPadrao > 0 ? $valorPadrao : $fallback;
        }

        return max((float) $valorInformado, 0);
    }

    private function montarResumoExportacao(Collection $vendas, ?string $statusFiltro): array
    {
        $total = (float) $vendas->sum(fn (Venda $venda) => (float) $venda->total);
        $quantidade = $vendas->count();
        $ticketMedio = $quantidade > 0 ? $total / $quantidade : 0;

        return [
            'status' => $statusFiltro ? ucfirst((string) $statusFiltro) : 'Todos',
            'quantidade' => $quantidade,
            'total' => $total,
            'ticket_medio' => $ticketMedio,
            'confirmadas_concluidas' => $vendas->whereIn('status', ['confirmada', 'concluida'])->count(),
            'canceladas' => $vendas->where('status', 'cancelada')->count(),
            'notas_emitidas' => $vendas->where('status_nota_fiscal', 'emitida')->count(),
            'notas_pendentes' => $vendas->where('status_nota_fiscal', 'pendente')->count(),
        ];
    }

    private function exportarCsv(Collection $vendas, array $resumo)
    {
        $fileName = 'vendas_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.csv';

        return response()->streamDownload(function () use ($vendas, $resumo) {
            $saida = fopen('php://output', 'wb');

            fputcsv($saida, ['Listagem de Vendas'], ';');
            fputcsv($saida, [], ';');
            fputcsv($saida, ['Status aplicado', $resumo['status']], ';');
            fputcsv($saida, ['Quantidade', $resumo['quantidade']], ';');
            fputcsv($saida, ['Valor total', number_format($resumo['total'], 2, ',', '.')], ';');
            fputcsv($saida, ['Ticket médio', number_format($resumo['ticket_medio'], 2, ',', '.')], ';');
            fputcsv($saida, ['Confirmadas/Concluídas', $resumo['confirmadas_concluidas']], ';');
            fputcsv($saida, ['Canceladas', $resumo['canceladas']], ';');
            fputcsv($saida, ['Notas emitidas', $resumo['notas_emitidas']], ';');
            fputcsv($saida, ['Notas pendentes', $resumo['notas_pendentes']], ';');
            fputcsv($saida, [], ';');

            fputcsv($saida, ['Número', 'Data', 'Cliente', 'Vendedor', 'Status', 'Itens', 'Total', 'Lucro', 'Nota Fiscal', 'Nº NF'], ';');

            foreach ($vendas as $venda) {
                fputcsv($saida, [
                    $venda->numero,
                    optional($venda->data_venda)->format('d/m/Y H:i') ?? '',
                    $venda->cliente?->nome,
                    $venda->vendedor?->user?->name,
                    ucfirst((string) $venda->status),
                    $venda->itens->count(),
                    number_format((float) $venda->total, 2, ',', '.'),
                    number_format((float) $venda->lucro_total, 2, ',', '.'),
                    str_replace('_', ' ', (string) ($venda->status_nota_fiscal ?: 'nao_emitir')),
                    $venda->nota_fiscal_numero,
                ], ';');
            }

            fclose($saida);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportarXlsx(Collection $vendas, array $resumo)
    {
        return app(XlsxExporter::class)->download(
            'vendas_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.xlsx',
            'Listagem de Vendas',
            [
                ['Status aplicado', $resumo['status']],
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor total', number_format($resumo['total'], 2, ',', '.')],
                ['Ticket médio', number_format($resumo['ticket_medio'], 2, ',', '.')],
                ['Confirmadas/Concluídas', (string) $resumo['confirmadas_concluidas']],
                ['Canceladas', (string) $resumo['canceladas']],
                ['Notas emitidas', (string) $resumo['notas_emitidas']],
                ['Notas pendentes', (string) $resumo['notas_pendentes']],
            ],
            [[
                'title' => 'Vendas',
                'sheet_title' => 'Vendas',
                'columns' => [
                    ['label' => 'Número'],
                    ['label' => 'Data'],
                    ['label' => 'Cliente'],
                    ['label' => 'Vendedor'],
                    ['label' => 'Status'],
                    ['label' => 'Itens'],
                    ['label' => 'Total'],
                    ['label' => 'Lucro'],
                    ['label' => 'Nota Fiscal'],
                    ['label' => 'Nº NF'],
                ],
                'rows' => $vendas->map(fn (Venda $venda) => [
                    (string) $venda->numero,
                    optional($venda->data_venda)->format('d/m/Y H:i') ?? '-',
                    $venda->cliente?->nome ?? '-',
                    $venda->vendedor?->user?->name ?? '-',
                    ucfirst((string) $venda->status),
                    (string) $venda->itens->count(),
                    number_format((float) $venda->total, 2, ',', '.'),
                    number_format((float) $venda->lucro_total, 2, ',', '.'),
                    str_replace('_', ' ', (string) ($venda->status_nota_fiscal ?: 'nao_emitir')),
                    $venda->nota_fiscal_numero ?: '-',
                ])->all(),
            ]]
        );
    }

    private function exportarPdf(?Empresa $empresa, Collection $vendas, array $resumo)
    {
        return app(PdfExporter::class)->download(
            'vendas_'.strtolower(str_replace(' ', '_', $resumo['status'])).'.pdf',
            'Listagem de Vendas',
            'Status aplicado: '.$resumo['status'],
            [
                ['Quantidade', (string) $resumo['quantidade']],
                ['Valor total', 'R$ '.number_format($resumo['total'], 2, ',', '.')],
                ['Ticket médio', 'R$ '.number_format($resumo['ticket_medio'], 2, ',', '.')],
                ['Confirmadas/Concluídas', (string) $resumo['confirmadas_concluidas']],
                ['Canceladas', (string) $resumo['canceladas']],
                ['Notas emitidas', (string) $resumo['notas_emitidas']],
                ['Notas pendentes', (string) $resumo['notas_pendentes']],
            ],
            [[
                'title' => 'Vendas filtradas',
                'columns' => [
                    ['label' => 'Número', 'width' => '8%'],
                    ['label' => 'Data', 'width' => '14%'],
                    ['label' => 'Cliente', 'width' => '20%'],
                    ['label' => 'Vendedor', 'width' => '16%'],
                    ['label' => 'Status', 'width' => '10%'],
                    ['label' => 'Itens', 'class' => 'num', 'width' => '8%'],
                    ['label' => 'Total', 'class' => 'num', 'width' => '10%'],
                    ['label' => 'NF', 'width' => '9%'],
                    ['label' => 'Nº NF', 'width' => '10%'],
                ],
                'rows' => $vendas->map(fn (Venda $venda) => [
                    (string) $venda->numero,
                    optional($venda->data_venda)->format('d/m/Y H:i') ?? '-',
                    $venda->cliente?->nome ?? '-',
                    $venda->vendedor?->user?->name ?? '-',
                    ucfirst((string) $venda->status),
                    (string) $venda->itens->count(),
                    'R$ '.number_format((float) $venda->total, 2, ',', '.'),
                    str_replace('_', ' ', (string) ($venda->status_nota_fiscal ?: 'nao_emitir')),
                    $venda->nota_fiscal_numero ?: '-',
                ])->all(),
            ]],
            $empresa
        );
    }
}
