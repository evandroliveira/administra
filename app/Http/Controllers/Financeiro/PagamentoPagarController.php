<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Requests\Financeiro\StorePagamentoPagarRequest;
use App\Models\ContaPagar;
use App\Models\PagamentoPagar;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PagamentoPagarController extends Controller
{
    public function store(StorePagamentoPagarRequest $request, ContaPagar $contaPagar)
    {
        $empresaId = $this->empresaId($request);
        abort_unless((int) $contaPagar->empresa_id === $empresaId, Response::HTTP_FORBIDDEN, 'Conta fora do escopo da empresa.');

        $pagamento = new PagamentoPagar();
        $pagamento->fill($request->validated());
        $pagamento->empresa_id = $empresaId;
        $pagamento->conta_id = $contaPagar->id;
        $pagamento->save();

        if (! $request->expectsJson()) {
            return redirect()
                ->route('contas.pagar.show', $contaPagar)
                ->with('status', 'Pagamento lançado com sucesso.');
        }

        return response()->json([
            'message' => 'Pagamento lançado com sucesso.',
            'pagamento' => $pagamento,
            'conta' => $contaPagar->refresh(),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, PagamentoPagar $pagamentoPagar)
    {
        $empresaId = $this->empresaId($request);
        abort_unless((int) $pagamentoPagar->empresa_id === $empresaId, Response::HTTP_FORBIDDEN, 'Pagamento fora do escopo da empresa.');

        $pagamentoPagar->delete();

        if (! $request->expectsJson()) {
            return redirect()
                ->route('contas.pagar.show', $pagamentoPagar->conta)
                ->with('status', 'Pagamento estornado com sucesso.');
        }

        return response()->json([
            'message' => 'Pagamento estornado com sucesso.',
        ]);
    }

    private function empresaId(Request $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        return (int) $empresaId;
    }
}
