<?php

namespace App\Support\Billing;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Fatura;
use App\Models\Plano;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class BillingService
{
    public function ensureDefaultPlan(): Plano
    {
        $defaults = config('billing.default_plan');

        return Plano::query()->updateOrCreate(
            ['nome' => $defaults['nome']],
            [
                'descricao' => $defaults['descricao'],
                'valor_mensal' => $defaults['valor_mensal'],
                'limite_usuarios' => $defaults['limite_usuarios'],
                'limite_produtos' => $defaults['limite_produtos'],
                'permite_promissoria' => $defaults['permite_promissoria'],
                'permite_relatorios_pdf' => $defaults['permite_relatorios_pdf'],
                'permite_exportacao_xlsx' => $defaults['permite_exportacao_xlsx'],
                'ativo' => $defaults['ativo'],
            ]
        );
    }

    public function ensureCurrentSubscription(Empresa $empresa): Assinatura
    {
        $assinatura = $empresa->assinaturas()->latest('id')->first();

        if ($assinatura) {
            return $assinatura->loadMissing('plano');
        }

        $plano = $this->ensureDefaultPlan();

        return Assinatura::query()->create([
            'empresa_id' => $empresa->id,
            'plano_id' => $plano->id,
            'status' => 'ativa',
            'inicio_vigencia' => now()->toDateString(),
            'fim_periodo_atual' => now()->addMonth()->toDateString(),
        ])->load('plano');
    }

    /**
     * @return Collection<int, Plano>
     */
    public function availablePlans(): Collection
    {
        $this->ensureDefaultPlan();

        return Plano::query()
            ->where('ativo', true)
            ->orderBy('valor_mensal')
            ->orderBy('limite_usuarios')
            ->orderBy('limite_produtos')
            ->get();
    }

    public function currentSubscriptionByEmpresaId(int $empresaId): ?Assinatura
    {
        $empresa = Empresa::query()->find($empresaId);

        if (! $empresa) {
            return null;
        }

        return $this->ensureCurrentSubscription($empresa);
    }

    public function featureEnabled(?Assinatura $assinatura, string $attribute): bool
    {
        if (! $assinatura || ! $assinatura->plano) {
            return true;
        }

        return (bool) ($assinatura->plano->{$attribute} ?? false);
    }

    public function deniedFeatureResponse(Request $request, string $message): Response|RedirectResponse
    {
        if ($request->query('export')) {
            return response($message, 403, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        return redirect()
            ->route('assinatura.show')
            ->with('warning', $message);
    }

    public function usageSummary(Empresa $empresa, Assinatura $assinatura): array
    {
        $usuariosAtivos = $empresa->usuariosVendas()->where('ativo', true)->count();
        $produtosCadastrados = $empresa->produtos()->count();

        return [
            'usuarios_ativos' => $usuariosAtivos,
            'produtos_cadastrados' => $produtosCadastrados,
            'limite_usuarios' => $assinatura->plano->limite_usuarios,
            'limite_produtos' => $assinatura->plano->limite_produtos,
            'usuarios_restantes' => max($assinatura->plano->limite_usuarios - $usuariosAtivos, 0),
            'produtos_restantes' => max($assinatura->plano->limite_produtos - $produtosCadastrados, 0),
        ];
    }

    public function changePlan(Assinatura $assinatura, Plano $plano): Assinatura
    {
        $assinatura->loadMissing('plano');

        if ($assinatura->plano_id === $plano->id) {
            return $assinatura;
        }

        $attributes = [
            'plano_id' => $plano->id,
        ];

        if ((float) $plano->valor_mensal <= 0) {
            $this->archiveOpenInvoicesForFreePlan($assinatura);
            $attributes['status'] = 'ativa';
            $attributes['trial_ends_at'] = null;
            $attributes['ativa_ate'] = now()->addMonth()->toDateString();
            $attributes['fim_periodo_atual'] = now()->addMonth()->toDateString();
        }

        $assinatura->forceFill($attributes)->save();

        return $assinatura->fresh('plano');
    }

    private function archiveOpenInvoicesForFreePlan(Assinatura $assinatura): void
    {
        Fatura::query()
            ->where('assinatura_id', $assinatura->id)
            ->whereIn('status', ['pendente', 'atrasada'])
            ->get()
            ->each(function (Fatura $fatura): void {
                $statusAnterior = $fatura->status;
                $payloadAtual = is_array($fatura->payload) ? $fatura->payload : [];
                $payloadAtual['local_cancellation'] = [
                    'reason' => 'migrated_to_free_plan',
                    'previous_status' => $statusAnterior,
                    'cancelled_at' => now()->toAtomString(),
                ];

                $fatura->forceFill([
                    'status' => 'cancelada',
                    'checkout_url' => '',
                    'invoice_url' => '',
                    'payload' => $payloadAtual,
                ])->save();
            });
    }

    public function userLimitReached(Empresa $empresa, ?Assinatura $assinatura = null): bool
    {
        $assinatura ??= $this->ensureCurrentSubscription($empresa);
        $limite = (int) ($assinatura->plano?->limite_usuarios ?? 0);

        if ($limite <= 0) {
            return false;
        }

        return $empresa->usuariosVendas()->where('ativo', true)->count() >= $limite;
    }

    public function productLimitReached(Empresa $empresa, ?Assinatura $assinatura = null): bool
    {
        $assinatura ??= $this->ensureCurrentSubscription($empresa);
        $limite = (int) ($assinatura->plano?->limite_produtos ?? 0);

        if ($limite <= 0) {
            return false;
        }

        return $empresa->produtos()->count() >= $limite;
    }

    public function billingConfigured(): bool
    {
        return $this->provider() === 'asaas'
            && trim((string) config('billing.asaas.api_key', '')) !== '';
    }

    public function providerLabel(): string
    {
        return match ($this->provider()) {
            'asaas' => 'Asaas',
            '' => 'Modo local',
            default => ucfirst($this->provider()),
        };
    }

    public function provider(): string
    {
        return strtolower(trim((string) config('billing.provider', '')));
    }
}