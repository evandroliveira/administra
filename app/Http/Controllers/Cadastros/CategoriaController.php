<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\StoreCategoriaRequest;
use App\Http\Requests\Cadastros\UpdateCategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $query = Categoria::query()->withCount('produtos')->where('empresa_id', $empresaId)->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q');

            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('descricao', 'like', '%'.$q.'%');
            });
        }

        $categorias = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($categorias);
        }

        return view('categorias.index', [
            'categorias' => $categorias,
            'filtros' => [
                'q' => (string) $request->input('q', ''),
            ],
        ]);
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(StoreCategoriaRequest $request)
    {
        $empresaId = $this->empresaId($request);

        $categoria = Categoria::query()->create(array_merge(
            $request->safe()->only(['nome', 'descricao', 'ativo']),
            ['empresa_id' => $empresaId],
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Categoria criada com sucesso.',
                'categoria' => [
                    'id' => $categoria->id,
                    'nome' => $categoria->nome,
                    'descricao' => $categoria->descricao,
                    'ativo' => (bool) $categoria->ativo,
                ],
            ], Response::HTTP_CREATED);
        }

        return redirect()->route('categorias.index')->with('status', 'Categoria criada com sucesso.');
    }

    public function edit(Request $request, Categoria $categoria)
    {
        $this->assertEmpresa($request, (int) $categoria->empresa_id);

        return view('categorias.edit', ['categoria' => $categoria]);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        $this->assertEmpresa($request, (int) $categoria->empresa_id);
        $categoria->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Categoria atualizada com sucesso.',
                'categoria' => $categoria->fresh(),
            ]);
        }

        return redirect()->route('categorias.index')->with('status', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        $this->assertEmpresa($request, (int) $categoria->empresa_id);

        if ($categoria->produtos()->exists()) {
            $categoria->update(['ativo' => false]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Categoria possui produtos vinculados e foi inativada em vez de ser excluida.',
                    'categoria' => $categoria->fresh(),
                ]);
            }

            return redirect()->route('categorias.index')->with('status', 'Categoria possui produtos vinculados e foi inativada em vez de ser excluida.');
        }

        $categoria->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Categoria removida com sucesso.']);
        }

        return redirect()->route('categorias.index')->with('status', 'Categoria removida com sucesso.');
    }

    private function empresaId(Request $request): int
    {
        $empresaId = optional($request->user()?->usuarioVendas)->empresa_id;
        abort_unless($empresaId, Response::HTTP_FORBIDDEN, 'Usuario sem empresa vinculada.');

        return (int) $empresaId;
    }

    private function assertEmpresa(Request $request, int $empresaId): void
    {
        abort_unless($this->empresaId($request) === $empresaId, Response::HTTP_FORBIDDEN, 'Recurso fora do escopo da empresa.');
    }
}