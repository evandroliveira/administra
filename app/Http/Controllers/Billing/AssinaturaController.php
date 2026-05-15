<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use App\Support\Billing\AsaasGateway;
use App\Support\Billing\BillingConfigurationException;
use App\Support\Billing\BillingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssinaturaController extends Controller
{
    public function show(Request $request, BillingService $billingService)
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;
        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $assinatura = $billingService->ensureCurrentSubscription($empresa);
        $faturas = Fatura::query()
            ->where('empresa_id', $empresa->id)
            ->with('assinatura.plano')
            ->orderByDesc('vencimento')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return view('billing.show', [
            'empresa' => $empresa,
            'assinatura' => $assinatura,
            'faturas' => $faturas,
            'cobrancaConfigurada' => $billingService->billingConfigured(),
            'usuarioAdminEmpresa' => $request->user()?->hasRole('admin') ?? false,
            'planoPadrao' => $billingService->ensureDefaultPlan(),
            'resumoUso' => $billingService->usageSummary($empresa, $assinatura),
            'billingGraceDays' => (int) config('billing.grace_days', 0),
            'providerLabel' => $billingService->providerLabel(),
        ]);
    }

    public function createCharge(Request $request, BillingService $billingService, AsaasGateway $asaasGateway)
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;
        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        try {
            $response = $asaasGateway->syncSubscription(
                $assinatura,
                'UNDEFINED',
                now()->toDateString(),
            );

            if (($response['mode'] ?? null) === 'free-plan') {
                return redirect()
                    ->route('assinatura.show')
                    ->with('status', 'A assinatura foi ativada no plano gratuito, sem necessidade de gateway.');
            }

            $checkoutUrl = $asaasGateway->checkoutUrl($response);
            if ($checkoutUrl !== '') {
                return redirect()->away($checkoutUrl);
            }

            return redirect()
                ->route('assinatura.show')
                ->with('status', 'Cobrança recorrente sincronizada com sucesso no gateway configurado.');
        } catch (BillingConfigurationException $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', 'Não foi possível sincronizar a cobrança recorrente: '.$exception->getMessage());
        }
    }
}