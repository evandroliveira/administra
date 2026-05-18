<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use App\Support\Brasil\BrazilianDocument;
use App\Support\Brasil\BrazilianPhone;
use App\Support\Billing\AsaasGateway;
use App\Support\Billing\BillingConfigurationException;
use App\Support\Billing\BillingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(BillingService $billingService): View
    {
        return view('auth.register', [
            'planoPadrao' => $billingService->ensureDefaultPlan(),
            'cobrancaConfigurada' => $billingService->billingConfigured(),
            'providerLabel' => $billingService->providerLabel(),
        ]);
    }

    public function store(Request $request, BillingService $billingService, AsaasGateway $asaasGateway): RedirectResponse
    {
        $plano = $billingService->ensureDefaultPlan();
        $cobrancaConfigurada = $billingService->billingConfigured() && (float) $plano->valor_mensal > 0;

        $request->validate([
            'nome_empresa' => ['required', 'string', 'max:150', Rule::unique('empresas', 'nome')],
            'documento' => ['nullable', 'string', 'max:20', Rule::requiredIf($cobrancaConfigurada), function (string $attribute, mixed $value, \Closure $fail): void {
                $documento = BrazilianDocument::digits((string) $value);

                if ($documento !== '' && ! BrazilianDocument::isValid($documento)) {
                    $fail('Informe um CPF ou CNPJ válido para a empresa.');
                }
            }],
            'email_empresa' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'telefone_empresa' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                $telefone = BrazilianPhone::digits((string) $value);

                if ($telefone !== '' && ! BrazilianPhone::isValid($telefone)) {
                    $fail('Informe um telefone válido com DDD.');
                }
            }],
            'logo' => ['nullable', 'image', 'max:2048'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $perfilAdmin = Perfil::ensureCanonicalProfile(Perfil::ADMIN);
        $hoje = now()->toDateString();

        [$empresa, $user, $assinatura] = DB::transaction(function () use ($request, $perfilAdmin, $plano, $cobrancaConfigurada, $hoje): array {
            $empresa = Empresa::query()->create([
                'nome' => trim((string) $request->string('nome_empresa')),
                'slug' => $this->resolveUniqueEmpresaSlug((string) $request->string('nome_empresa')),
                'documento' => BrazilianDocument::digits((string) $request->input('documento')) ?: null,
                'email' => trim((string) $request->input('email_empresa')) ?: trim((string) $request->input('email')),
                'telefone' => BrazilianPhone::digits((string) $request->input('telefone_empresa')) ?: null,
                'ativa' => true,
            ]);

            if ($request->hasFile('logo')) {
                $empresa->forceFill([
                    'logo' => $request->file('logo')->store('empresas/logos', 'public'),
                ])->save();
            }

            $user = User::query()->create([
                'username' => trim((string) $request->input('username')),
                'name' => trim((string) $request->input('name')),
                'email' => trim((string) $request->input('email')),
                'password' => Hash::make((string) $request->input('password')),
            ]);

            $user->assignRole(Perfil::ADMIN);

            $user->usuarioVendas()->create([
                'empresa_id' => $empresa->id,
                'perfil_id' => $perfilAdmin->id,
                'telefone' => BrazilianPhone::digits((string) $request->input('telefone_empresa')) ?: null,
                'ativo' => true,
                'data_contratacao' => $hoje,
            ]);

            $assinatura = Assinatura::query()->create([
                'empresa_id' => $empresa->id,
                'plano_id' => $plano->id,
                'status' => $cobrancaConfigurada ? 'inadimplente' : 'ativa',
                'inicio_vigencia' => $hoje,
                'fim_periodo_atual' => $cobrancaConfigurada ? $hoje : now()->addMonth()->toDateString(),
            ]);

            return [$empresa, $user, $assinatura];
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        if ($cobrancaConfigurada) {
            try {
                $response = $asaasGateway->syncSubscription(
                    $assinatura,
                    (string) config('billing.asaas.billing_type', 'UNDEFINED'),
                    $hoje,
                );

                $checkoutUrl = $asaasGateway->checkoutUrl($response);
                if ($checkoutUrl !== '') {
                    return redirect()->away($checkoutUrl);
                }

                return redirect()
                    ->route('assinatura.show')
                    ->with('status', 'Empresa criada com sucesso. A cobrança recorrente foi sincronizada no gateway configurado.');
            } catch (BillingConfigurationException $exception) {
                return redirect()
                    ->route('assinatura.show')
                    ->with('warning', $exception->getMessage());
            } catch (\Throwable $exception) {
                return redirect()
                    ->route('assinatura.show')
                    ->with('warning', 'Empresa criada, mas não foi possível abrir o pagamento recorrente automaticamente: '.$exception->getMessage());
            }
        }

        return redirect()
            ->route('assinatura.show')
            ->with('status', 'Empresa criada com sucesso.');
    }

    private function resolveUniqueEmpresaSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'empresa';
        $suffix = 2;

        while (Empresa::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug !== '' ? $baseSlug.'-'.$suffix : 'empresa-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
