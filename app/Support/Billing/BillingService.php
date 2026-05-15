<?php

namespace App\Support\Billing;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Plano;

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