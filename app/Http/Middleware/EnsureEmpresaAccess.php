<?php

namespace App\Http\Middleware;

use App\Support\Billing\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaAccess
{
    public function __construct(private readonly BillingService $billingService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('assinatura.*') || $request->routeIs('profile.*')) {
            return $next($request);
        }

        $empresa = $request->user()?->usuarioVendas?->empresa;
        if (! $empresa) {
            return $next($request);
        }

        if (! $empresa->ativa) {
            return redirect()
                ->route('assinatura.show')
                ->with('warning', 'A empresa está inativa. Regularize o cadastro para continuar.');
        }

        $assinatura = $empresa->assinaturaAtual()->with('plano')->first()
            ?? $this->billingService->ensureCurrentSubscription($empresa);

        if ($assinatura->precisaRegularizar()) {
            $message = $assinatura->status === 'suspensa'
                ? 'A assinatura da empresa foi suspensa automaticamente por atraso acima da carência configurada.'
                : 'A assinatura da empresa precisa de regularização para liberar o uso do sistema.';

            return redirect()
                ->route('assinatura.show')
                ->with('warning', $message);
        }

        return $next($request);
    }
}