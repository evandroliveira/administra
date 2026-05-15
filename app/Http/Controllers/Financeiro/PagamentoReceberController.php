<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Requests\Financeiro\StorePagamentoReceberRequest;
use App\Models\ContaReceber;
use App\Models\PagamentoReceber;
use App\Models\PromissoriaParcela;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PagamentoReceberController extends Controller
{
    public function store(StorePagamentoReceberRequest $request, ContaReceber $contaReceber)
    {
        $empresaId = $this->empresaId($request);
        abort_unless((int) $contaReceber->empresa_id === $empresaId, Response::HTTP_FORBIDDEN, 'Conta fora do escopo da empresa.');

        $dados = $request->validated();

        if (! empty($dados['promissoria_parcela_id'])) {
            $parcela = PromissoriaParcela::query()
                ->whereKey((int) $dados['promissoria_parcela_id'])
                ->where('empresa_id', $empresaId)
                ->first();

            abort_unless($parcela, Response::HTTP_UNPROCESSABLE_ENTITY, 'Parcela de promissória inválida para a empresa.');
            abort_unless((int) $parcela->promissoria->conta_id === (int) $contaReceber->id, Response::HTTP_UNPROCESSABLE_ENTITY, 'Parcela não pertence à conta informada.');
        }

        $pagamento = DB::transaction(function () use ($contaReceber, $dados, $empresaId) {
            $pagamento = new PagamentoReceber();
            $pagamento->fill($dados);
            $pagamento->empresa_id = $empresaId;
            $pagamento->conta_id = $contaReceber->id;
            $pagamento->save();

            return $pagamento;
        });

        $contaReceber->refresh()->load(['pagamentos', 'promissoria.parcelas']);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('contas.receber.show', $contaReceber)
                ->with('status', 'Pagamento registrado com sucesso.');
        }

        return response()->json([
            'message' => 'Pagamento registrado com sucesso.',
            'pagamento' => $pagamento,
            'conta' => $contaReceber,
        ], Response::HTTP_CREATED);
    }

    private function empresaId(StorePagamentoReceberRequest $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');
        return (int) $empresaId;
    }
}
