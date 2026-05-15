<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\Cadastros\StoreClienteRequest;
use App\Http\Requests\Cadastros\UpdateClienteRequest;
use App\Models\Cliente;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $query = Cliente::query()->where('empresa_id', $empresaId)->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('cpf_cnpj', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        $clientes = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($clientes);
        }

        return view('clientes.index', [
            'clientes' => $clientes,
            'filtros' => [
                'q' => (string) $request->input('q', ''),
            ],
        ]);
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
        $dados = $request->validated();
        $dados['empresa_id'] = $this->empresaId($request);
        $dados['credito_disponivel'] = $dados['limite_credito'] ?? 0;

        $cliente = Cliente::create($dados);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cliente criado com sucesso.', 'cliente' => $cliente], Response::HTTP_CREATED);
        }

        return redirect()->route('clientes.show', $cliente)->with('status', 'Cliente criado com sucesso.');
    }

    public function show(Request $request, Cliente $cliente)
    {
        $this->assertEmpresa($request, (int) $cliente->empresa_id);
        $cliente->load(['vendas', 'contasReceber']);

        if ($request->expectsJson()) {
            return response()->json($cliente);
        }

        return view('clientes.show', ['cliente' => $cliente]);
    }

    public function edit(Request $request, Cliente $cliente)
    {
        $this->assertEmpresa($request, (int) $cliente->empresa_id);
        return view('clientes.edit', ['cliente' => $cliente]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $this->assertEmpresa($request, (int) $cliente->empresa_id);
        $dados = $request->validated();

        $dados['credito_disponivel'] = max(
            (float) ($dados['limite_credito'] ?? $cliente->limite_credito) - (float) ($cliente->limite_credito - $cliente->credito_disponivel),
            0
        );

        $cliente->update($dados);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cliente atualizado com sucesso.', 'cliente' => $cliente]);
        }

        return redirect()->route('clientes.show', $cliente)->with('status', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Request $request, Cliente $cliente)
    {
        $this->assertEmpresa($request, (int) $cliente->empresa_id);
        $cliente->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cliente removido com sucesso.']);
        }

        return redirect()->route('clientes.index')->with('status', 'Cliente removido com sucesso.');
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
