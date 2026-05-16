<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioVendas;
use App\Support\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UsuarioEmpresaController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $this->empresa($request);
        $empresaId = $empresa->id;

        $usuarios = UsuarioVendas::query()
            ->with(['user', 'perfil'])
            ->where('empresa_id', $empresaId)
            ->get()
            ->sortBy([
                ['ativo', 'desc'],
                fn (UsuarioVendas $usuarioVendas) => mb_strtolower((string) $usuarioVendas->user?->name),
                fn (UsuarioVendas $usuarioVendas) => mb_strtolower((string) $usuarioVendas->user?->username),
            ])
            ->values();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'resumo' => $this->resumoUsuarios($empresa),
        ]);
    }

    public function create(Request $request): View
    {
        $empresa = $this->empresa($request);

        return view('usuarios.form', [
            'modo' => 'create',
            'usuarioEdicao' => null,
            'perfis' => $this->perfis(),
            'resumo' => $this->resumoUsuarios($empresa),
            'valores' => [
                'username' => old('username', ''),
                'name' => old('name', ''),
                'email' => old('email', ''),
                'perfil' => old('perfil', Perfil::VENDEDOR),
                'telefone' => old('telefone', ''),
                'endereco' => old('endereco', ''),
                'cidade' => old('cidade', ''),
                'estado' => old('estado', ''),
                'cep' => old('cep', ''),
                'ativo' => old('ativo', '1') === '1',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $this->empresa($request);
        $dados = $this->validarUsuario($request);
        $perfil = $this->resolverPerfil($dados['perfil']);
        $ativo = (bool) $dados['ativo'];
        $billingService = app(BillingService::class);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        if ($ativo && $billingService->userLimitReached($empresa, $assinatura)) {
            return back()
                ->withErrors([
                    'ativo' => sprintf(
                        'O plano atual permite até %d usuário(s) ativo(s). Faça upgrade para cadastrar mais acessos.',
                        (int) $assinatura->plano->limite_usuarios,
                    ),
                ])
                ->withInput();
        }

        $user = User::create([
            'username' => $dados['username'],
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => $dados['password'],
        ]);
        $user->syncRoles([$perfil->nome]);

        UsuarioVendas::create([
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil->id,
            'telefone' => $dados['telefone'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'estado' => $dados['estado'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'ativo' => $ativo,
            'data_contratacao' => now()->toDateString(),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Usuário cadastrado com sucesso.');
    }

    public function edit(Request $request, UsuarioVendas $usuario): View
    {
        $usuario = $this->usuarioEmpresa($request, $usuario);
        $empresa = $this->empresa($request);

        return view('usuarios.form', [
            'modo' => 'edit',
            'usuarioEdicao' => $usuario,
            'perfis' => $this->perfis(),
            'resumo' => $this->resumoUsuarios($empresa),
            'valores' => [
                'username' => old('username', $usuario->user?->username),
                'name' => old('name', $usuario->user?->name),
                'email' => old('email', $usuario->user?->email),
                'perfil' => old('perfil', $usuario->perfil?->nome),
                'telefone' => old('telefone', $usuario->telefone),
                'endereco' => old('endereco', $usuario->endereco),
                'cidade' => old('cidade', $usuario->cidade),
                'estado' => old('estado', $usuario->estado),
                'cep' => old('cep', $usuario->cep),
                'ativo' => old('ativo', $usuario->ativo ? '1' : '0') === '1',
            ],
        ]);
    }

    public function update(Request $request, UsuarioVendas $usuario): RedirectResponse
    {
        $usuario = $this->usuarioEmpresa($request, $usuario);
        $dados = $this->validarUsuario($request, $usuario->user_id);
        $perfil = $this->resolverPerfil($dados['perfil']);
        $ativo = (bool) $dados['ativo'];
        $empresa = $usuario->empresa()->firstOrFail();
        $billingService = app(BillingService::class);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        if ($ativo && ! $usuario->ativo && $billingService->userLimitReached($empresa, $assinatura)) {
            return back()
                ->withErrors([
                    'ativo' => sprintf(
                        'O plano atual permite até %d usuário(s) ativo(s). Faça upgrade para reativar este acesso.',
                        (int) $assinatura->plano->limite_usuarios,
                    ),
                ])
                ->withInput();
        }

        if (! ($ativo && $perfil->nome === Perfil::ADMIN) && $this->contagemAdminsAtivos($usuario->empresa_id, $usuario->id) === 0) {
            return back()
                ->withErrors(['perfil' => 'A empresa precisa manter pelo menos um administrador ativo.'])
                ->withInput();
        }

        $user = $usuario->user;
        $user->username = $dados['username'];
        $user->name = $dados['name'];
        $user->email = $dados['email'];
        if (! empty($dados['password'])) {
            $user->password = $dados['password'];
        }
        $user->save();
        $user->syncRoles([$perfil->nome]);

        $usuario->update([
            'perfil_id' => $perfil->id,
            'telefone' => $dados['telefone'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'estado' => $dados['estado'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'ativo' => $ativo,
        ]);

        if ($request->user()?->id === $user->id && ! $ativo) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Seu acesso foi desativado e a sessão foi encerrada.');
        }

        if ($request->user()?->id === $user->id && $perfil->nome !== Perfil::ADMIN) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Seu perfil foi atualizado com sucesso.');
        }

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Usuário atualizado com sucesso.');
    }

    private function validarUsuario(Request $request, ?int $userId = null): array
    {
        $regrasSenha = $userId
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'perfil' => ['required', 'string', Rule::in([Perfil::ADMIN, Perfil::GERENTE, Perfil::VENDEDOR, Perfil::RECEPCAO])],
            'telefone' => ['nullable', 'string', 'max:30'],
            'endereco' => ['nullable', 'string', 'max:500'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'in:0,1'],
            'password' => $regrasSenha,
        ] + ['password_confirmation' => ['nullable', 'string']]);
    }

    private function usuarioEmpresa(Request $request, UsuarioVendas $usuario): UsuarioVendas
    {
        abort_unless($usuario->empresa_id === $this->empresaId($request), Response::HTTP_NOT_FOUND);

        return $usuario->loadMissing(['user', 'perfil']);
    }

    private function perfis(): Collection
    {
        $ordem = [Perfil::ADMIN => 1, Perfil::GERENTE => 2, Perfil::VENDEDOR => 3, Perfil::RECEPCAO => 4];

        return Perfil::query()
            ->get()
            ->sortBy(fn (Perfil $perfil) => $ordem[$perfil->nome] ?? 99)
            ->values();
    }

    private function resumoUsuarios(Empresa $empresa): array
    {
        $billingService = app(BillingService::class);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);
        $usuarios = UsuarioVendas::query()
            ->with('user')
            ->where('empresa_id', $empresa->id)
            ->get();
        $uso = $billingService->usageSummary($empresa, $assinatura);

        return [
            'total' => $usuarios->count(),
            'ativos' => $usuarios->where('ativo', true)->count(),
            'inativos' => $usuarios->where('ativo', false)->count(),
            'admins_ativos' => $usuarios->filter(function (UsuarioVendas $usuarioVendas) {
                return $usuarioVendas->ativo && $usuarioVendas->user?->hasRole(Perfil::ADMIN);
            })->count(),
            'limite_usuarios' => $uso['limite_usuarios'],
            'usuarios_restantes' => $uso['usuarios_restantes'],
            'limite_atingido' => $billingService->userLimitReached($empresa, $assinatura),
        ];
    }

    private function contagemAdminsAtivos(int $empresaId, ?int $excluirUsuarioVendasId = null): int
    {
        return User::query()
            ->role(Perfil::ADMIN)
            ->whereHas('usuarioVendas', function ($query) use ($empresaId, $excluirUsuarioVendasId) {
                $query->where('empresa_id', $empresaId)
                    ->where('ativo', true)
                    ->when($excluirUsuarioVendasId, fn ($inner) => $inner->where('id', '!=', $excluirUsuarioVendasId));
            })
            ->count();
    }

    private function resolverPerfil(string $nome): Perfil
    {
        return Perfil::query()->where('nome', $nome)->firstOrFail();
    }

    private function empresaId(Request $request): int
    {
        $empresaId = (int) optional($request->user()?->usuarioVendas)->empresa_id;

        abort_unless($empresaId > 0, Response::HTTP_FORBIDDEN, 'Usuario sem empresa vinculada.');

        return $empresaId;
    }

    private function empresa(Request $request): Empresa
    {
        return Empresa::query()->findOrFail($this->empresaId($request));
    }
}