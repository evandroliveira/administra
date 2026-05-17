<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\Cadastros\StoreProdutoRequest;
use App\Http\Requests\Cadastros\UpdateProdutoRequest;
use App\Models\Empresa;
use App\Models\Categoria;
use App\Models\Produto;
use App\Http\Controllers\Controller;
use App\Support\Billing\BillingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $this->empresa($request);
        $empresaId = $empresa->id;
        $resumoPlano = $this->resumoPlano($empresa);
        $categorias = Categoria::query()->where('empresa_id', $empresaId)->orderBy('nome')->get();
        $query = Produto::query()->with('categoria')->where('empresa_id', $empresaId)->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('codigo', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', (int) $request->input('categoria_id'));
        }

        $produtos = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($produtos);
        }

        return view('produtos.index', [
            'produtos' => $produtos,
            'categorias' => $categorias,
            'filtros' => [
                'q' => (string) $request->input('q', ''),
                'categoria_id' => (string) $request->input('categoria_id', ''),
            ],
            'resumoPlano' => $resumoPlano,
        ]);
    }

    public function create(Request $request)
    {
        $empresa = $this->empresa($request);
        $empresaId = $empresa->id;
        $categorias = Categoria::query()->where('empresa_id', $empresaId)->where('ativo', true)->orderBy('nome')->get();

        return view('produtos.create', [
            'categorias' => $categorias,
            'resumoPlano' => $this->resumoPlano($empresa),
        ]);
    }

    public function store(StoreProdutoRequest $request)
    {
        $empresa = $this->empresa($request);
        $billingService = app(BillingService::class);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        if ($billingService->productLimitReached($empresa, $assinatura)) {
            return back()
                ->withErrors([
                    'codigo' => sprintf(
                        'O plano atual permite até %d produto(s). Faça upgrade para cadastrar mais itens.',
                        (int) $assinatura->plano->limite_produtos,
                    ),
                ])
                ->withInput();
        }

        $dados = $request->validated();
        $dados['empresa_id'] = $empresa->id;
        $dados['custo_medio'] = (float) ($dados['custo_medio'] ?? 0) > 0 ? $dados['custo_medio'] : $dados['preco_custo'];
        $dados['margem_lucro'] = $this->calcularMargem((float) $dados['preco_custo'], (float) $dados['preco_venda']);

        $produto = Produto::create($dados);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produto criado com sucesso.', 'produto' => $produto], Response::HTTP_CREATED);
        }

        return redirect()->route('produtos.show', $produto)->with('status', 'Produto criado com sucesso.');
    }

    public function show(Request $request, Produto $produto)
    {
        $this->assertEmpresa($request, (int) $produto->empresa_id);
        $produto->load([
            'categoria',
            'imagens',
            'movimentacoesEstoque' => fn ($query) => $query->latest('created_at')->latest('id')->limit(10),
        ]);

        if ($request->expectsJson()) {
            return response()->json($produto);
        }

        return view('produtos.show', ['produto' => $produto]);
    }

    public function edit(Request $request, Produto $produto)
    {
        $this->assertEmpresa($request, (int) $produto->empresa_id);
        $categorias = Categoria::query()
            ->where('empresa_id', $produto->empresa_id)
            ->where(function ($query) use ($produto) {
                $query->where('ativo', true);

                if ($produto->categoria_id) {
                    $query->orWhere('id', $produto->categoria_id);
                }
            })
            ->orderBy('nome')
            ->get();

        return view('produtos.edit', ['produto' => $produto, 'categorias' => $categorias]);
    }

    public function update(UpdateProdutoRequest $request, Produto $produto)
    {
        $this->assertEmpresa($request, (int) $produto->empresa_id);
        $dados = $request->validated();
        $dados['custo_medio'] = (float) ($dados['custo_medio'] ?? 0) > 0 ? $dados['custo_medio'] : $dados['preco_custo'];
        $dados['margem_lucro'] = $this->calcularMargem((float) $dados['preco_custo'], (float) $dados['preco_venda']);

        $produto->update($dados);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produto atualizado com sucesso.', 'produto' => $produto]);
        }

        return redirect()->route('produtos.show', $produto)->with('status', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Produto $produto)
    {
        $this->assertEmpresa($request, (int) $produto->empresa_id);
        $produto->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produto removido com sucesso.']);
        }

        return redirect()->route('produtos.index')->with('status', 'Produto removido com sucesso.');
    }

    private function empresaId(Request $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        return (int) $empresaId;
    }

    private function empresa(Request $request): Empresa
    {
        return Empresa::query()->findOrFail($this->empresaId($request));
    }

    private function assertEmpresa(Request $request, int $empresaId): void
    {
        abort_unless($this->empresaId($request) === $empresaId, Response::HTTP_FORBIDDEN, 'Recurso fora do escopo da empresa.');
    }

    private function calcularMargem(float $precoCusto, float $precoVenda): float
    {
        if ($precoVenda <= 0) {
            return 0;
        }

        return round((($precoVenda - $precoCusto) / $precoVenda) * 100, 2);
    }

    private function resumoPlano(Empresa $empresa): array
    {
        $billingService = app(BillingService::class);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);
        $uso = $billingService->usageSummary($empresa, $assinatura);

        return [
            'produtos_cadastrados' => $uso['produtos_cadastrados'],
            'limite_produtos' => $uso['limite_produtos'],
            'produtos_restantes' => $uso['produtos_restantes'],
            'limite_atingido' => $billingService->productLimitReached($empresa, $assinatura),
        ];
    }
}
