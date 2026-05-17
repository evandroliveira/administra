<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use App\Models\Plano;
use App\Support\Billing\AsaasGateway;
use App\Support\Billing\BillingConfigurationException;
use App\Support\Billing\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AssinaturaController extends Controller
{
    public function show(Request $request, BillingService $billingService)
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;
        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $assinatura = $billingService->ensureCurrentSubscription($empresa);
        $planoGratuitoAtivo = (float) ($assinatura->plano->valor_mensal ?? 0) <= 0;
        $usuarioAdminEmpresa = $request->user()?->hasRole('admin') ?? false;
        $possuiFaturasHistoricas = Fatura::query()
            ->where('empresa_id', $empresa->id)
            ->exists();
        $faturaEmAberto = $planoGratuitoAtivo ? null : Fatura::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('status', ['pendente', 'atrasada'])
            ->where(function ($query) {
                $query
                    ->where(function ($subquery) {
                        $subquery->whereNotNull('checkout_url')->where('checkout_url', '!=', '');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->whereNotNull('invoice_url')->where('invoice_url', '!=', '');
                    });
            })
            ->orderByDesc('created_at')
            ->first();
        $faturaSemLinkUtil = $planoGratuitoAtivo ? null : Fatura::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('status', ['pendente', 'atrasada'])
            ->where(function ($query) {
                $query->whereNull('checkout_url')->orWhere('checkout_url', '');
            })
            ->where(function ($query) {
                $query->whereNull('invoice_url')->orWhere('invoice_url', '');
            })
            ->orderByDesc('created_at')
            ->first();
        $podeGerarNovaCobranca = $usuarioAdminEmpresa
            && ! $planoGratuitoAtivo
            && $billingService->billingConfigured()
            && (float) ($assinatura->plano->valor_mensal ?? 0) > 0
            && ! $faturaEmAberto
            && $assinatura->precisaRegularizar();

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
            'faturaEmAberto' => $faturaEmAberto,
            'faturaSemLinkUtil' => $faturaSemLinkUtil,
            'faturas' => $faturas,
            'cobrancaConfigurada' => $billingService->billingConfigured(),
            'usuarioAdminEmpresa' => $usuarioAdminEmpresa,
            'podeGerarNovaCobranca' => $podeGerarNovaCobranca,
            'planoGratuitoAtivo' => $planoGratuitoAtivo,
            'possuiFaturasHistoricas' => $possuiFaturasHistoricas,
            'planosDisponiveis' => $billingService->availablePlans(),
            'resumoUso' => $billingService->usageSummary($empresa, $assinatura),
            'billingGraceDays' => (int) config('billing.grace_days', 0),
            'providerLabel' => $billingService->providerLabel(),
        ]);
    }

    public function updatePlan(Request $request, BillingService $billingService, AsaasGateway $asaasGateway)
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;
        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $data = $request->validate([
            'plano_id' => [
                'required',
                'integer',
                Rule::exists('planos', 'id')->where(fn ($query) => $query->where('ativo', true)),
            ],
        ]);

        $assinatura = $billingService->ensureCurrentSubscription($empresa);
        $plano = Plano::query()->where('ativo', true)->findOrFail((int) $data['plano_id']);

        if ($assinatura->plano_id === $plano->id) {
            return redirect()
                ->route('assinatura.show')
                ->with('status', 'A empresa já está no plano '.$plano->nome.'.');
        }

        $assinatura = $billingService->changePlan($assinatura, $plano);

        try {
            if ($billingService->billingConfigured() && ((float) $plano->valor_mensal > 0 || $assinatura->gateway_subscription_id)) {
                $response = $asaasGateway->syncSubscription(
                    $assinatura,
                    'UNDEFINED',
                    now()->toDateString(),
                );

                if (($response['mode'] ?? null) === 'free-plan') {
                    return redirect()
                        ->route('assinatura.show')
                        ->with('status', 'Plano alterado para '.$plano->nome.' sem cobrança recorrente. A assinatura no '.$billingService->providerLabel().' foi suspensa e as cobranças abertas foram encerradas no histórico local.');
                }

                $checkoutUrl = $asaasGateway->checkoutUrl($response);
                if ($checkoutUrl !== '') {
                    return redirect()->away($checkoutUrl);
                }

                return redirect()
                    ->route('assinatura.show')
                    ->with('warning', 'Plano alterado para '.$plano->nome.', mas o gateway não retornou um link de pagamento utilizável para a cobrança atual.');
            }

            if ((float) $plano->valor_mensal <= 0) {
                return redirect()
                    ->route('assinatura.show')
                    ->with('status', 'Plano alterado para '.$plano->nome.' sem cobrança recorrente. As cobranças abertas foram encerradas no histórico local.');
            }

            return redirect()
                ->route('assinatura.show')
                ->with('status', 'Plano alterado para '.$plano->nome.'.');
        } catch (BillingConfigurationException $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', 'Não foi possível alterar o plano da assinatura: '.$exception->getMessage());
        }
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
            if ($checkoutUrl === '') {
                $faturaAcionavel = Fatura::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereIn('status', ['pendente', 'atrasada'])
                    ->where(function ($query) {
                        $query
                            ->where(function ($subquery) {
                                $subquery->whereNotNull('checkout_url')->where('checkout_url', '!=', '');
                            })
                            ->orWhere(function ($subquery) {
                                $subquery->whereNotNull('invoice_url')->where('invoice_url', '!=', '');
                            });
                    })
                    ->orderByDesc('created_at')
                    ->first();

                if ($faturaAcionavel) {
                    return redirect()->away($faturaAcionavel->checkout_url ?: $faturaAcionavel->invoice_url);
                }

                return redirect()
                    ->route('assinatura.show')
                    ->with('warning', 'A cobrança foi sincronizada, mas o gateway não retornou um link de pagamento utilizável para a fatura atual.');
            }

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

    public function regenerateCharge(Request $request, BillingService $billingService, AsaasGateway $asaasGateway)
    {
        $empresa = $request->user()?->usuarioVendas?->empresa;
        abort_unless($empresa, Response::HTTP_FORBIDDEN, 'Usuário sem empresa vinculada.');

        $assinatura = $billingService->ensureCurrentSubscription($empresa);

        try {
            $response = $asaasGateway->regenerateCharge(
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
                ->with('warning', 'A nova cobrança foi gerada, mas o gateway não retornou um link de pagamento utilizável.');
        } catch (BillingConfigurationException $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('assinatura.show')
                ->with('error', 'Não foi possível gerar uma nova cobrança para a assinatura: '.$exception->getMessage());
        }
    }
}