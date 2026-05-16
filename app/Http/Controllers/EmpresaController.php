<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Support\Brasil\BrazilianDocument;
use App\Support\Brasil\BrazilianPhone;
use App\Support\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EmpresaController extends Controller
{
    public function edit(Request $request, BillingService $billingService): View
    {
        $empresa = $this->empresa($request);
        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        return view('empresa.edit', [
            'empresa' => $empresa,
            'assinatura' => $assinatura,
            'resumoUso' => $billingService->usageSummary($empresa, $assinatura),
            'logoUrl' => $empresa->logo ? Storage::disk('public')->url($empresa->logo) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresa = $this->empresa($request);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150', Rule::unique('empresas', 'nome')->ignore($empresa->id)],
            'documento' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                $documento = BrazilianDocument::digits((string) $value);

                if ($documento !== '' && ! BrazilianDocument::isValid($documento)) {
                    $fail('Informe um CPF ou CNPJ válido para a empresa.');
                }
            }],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                $telefone = BrazilianPhone::digits((string) $value);

                if ($telefone !== '' && ! BrazilianPhone::isValid($telefone)) {
                    $fail('Informe um telefone válido com DDD.');
                }
            }],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remover_logo' => ['nullable', 'in:0,1'],
        ]);

        $logoAtual = $empresa->logo;

        if ($request->boolean('remover_logo') && $logoAtual) {
            Storage::disk('public')->delete($logoAtual);
            $empresa->logo = null;
        } elseif ($request->hasFile('logo')) {
            if ($logoAtual) {
                Storage::disk('public')->delete($logoAtual);
            }

            $empresa->logo = $request->file('logo')->store('empresas/logos', 'public');
        }

        $empresa->fill([
            'nome' => trim((string) $dados['nome']),
            'documento' => BrazilianDocument::digits((string) ($dados['documento'] ?? '')) ?: null,
            'email' => trim((string) ($dados['email'] ?? '')) ?: null,
            'telefone' => BrazilianPhone::digits((string) ($dados['telefone'] ?? '')) ?: null,
        ]);
        $empresa->save();

        return redirect()
            ->route('empresa.edit')
            ->with('status', 'Dados da empresa atualizados com sucesso.');
    }

    private function empresa(Request $request): Empresa
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;

        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        return $empresa;
    }
}