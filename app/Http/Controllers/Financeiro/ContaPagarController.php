<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Requests\Financeiro\StoreContaPagarRequest;
use App\Models\ContaPagar;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContaPagarController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);

        $query = ContaPagar::query()
            ->with('pagamentos')
            ->where('empresa_id', $empresaId)
            ->orderBy('data_vencimento');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $contas = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($contas);
        }

        return view('financeiro.contas_pagar.index', [
            'contas' => $contas,
            'filtros' => [
                'status' => (string) $request->input('status', ''),
            ],
        ]);
    }

    public function create()
    {
        return view('financeiro.contas_pagar.create');
    }

    public function show(Request $request, ContaPagar $contaPagar)
    {
        $this->assertEmpresa($request, (int) $contaPagar->empresa_id);
        $contaPagar->load('pagamentos');

        if ($request->expectsJson()) {
            return response()->json($contaPagar);
        }

        return view('financeiro.contas_pagar.show', [
            'conta' => $contaPagar,
        ]);
    }

    public function store(StoreContaPagarRequest $request)
    {
        $conta = new ContaPagar();
        $conta->fill($request->validated());
        $conta->empresa_id = $this->empresaId($request);
        $conta->save();

        if (! $request->expectsJson()) {
            return redirect()
                ->route('contas.pagar.show', $conta)
                ->with('status', 'Conta a pagar criada com sucesso.');
        }

        return response()->json([
            'message' => 'Conta a pagar criada com sucesso.',
            'conta' => $conta,
        ], Response::HTTP_CREATED);
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
}
