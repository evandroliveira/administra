<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\EventoWebhook;
use App\Models\Fatura;
use App\Models\Plano;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AsaasWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejeita_payload_json_invalido(): void
    {
        $response = $this->call(
            'POST',
            route('billing.webhooks.asaas'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            '{invalido'
        );

        $response->assertStatus(400)
            ->assertJson([
                'detail' => 'Payload JSON inválido.',
            ]);
    }

    public function test_rejeita_token_invalido(): void
    {
        Config::set('billing.asaas.webhook_token', 'token-webhook');

        $response = $this->postJson(route('billing.webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
        ], [
            'X-Asaas-Webhook-Token' => 'token-invalido',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'detail' => 'Token de webhook inválido para Asaas.',
            ]);

        $this->assertDatabaseCount('evento_webhooks', 0);
    }

    public function test_webhook_asaas_cria_fatura_e_reativa_assinatura(): void
    {
        Config::set('billing.asaas.webhook_token', 'token-webhook');

        $plano = Plano::query()->create([
            'nome' => 'Profissional',
            'descricao' => 'Plano profissional',
            'valor_mensal' => 249,
            'limite_usuarios' => 10,
            'limite_produtos' => 2000,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        $empresa = Empresa::query()->create([
            'nome' => 'Loja Cobrança',
            'slug' => 'loja-cobranca',
            'ativa' => true,
        ]);

        $assinatura = Assinatura::query()->create([
            'empresa_id' => $empresa->id,
            'plano_id' => $plano->id,
            'status' => 'inadimplente',
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_123',
            'gateway_subscription_id' => 'sub_123',
            'inicio_vigencia' => now()->subMonth()->toDateString(),
            'ativa_ate' => now()->subDay()->toDateString(),
        ]);

        $payload = [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_123',
                'customer' => 'cus_123',
                'subscription' => 'sub_123',
                'value' => '249.00',
                'status' => 'RECEIVED',
                'dueDate' => now()->toDateString(),
                'paymentDate' => now()->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_123',
            ],
        ];

        $response = $this->postJson(route('billing.webhooks.asaas'), $payload, [
            'X-Asaas-Webhook-Token' => 'token-webhook',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
            ]);

        $assinatura->refresh();
        $fatura = Fatura::query()->where('external_id', 'pay_123')->firstOrFail();
        $evento = EventoWebhook::query()->where('external_id', 'PAYMENT_RECEIVED:pay_123')->firstOrFail();

        $this->assertSame($empresa->id, $fatura->empresa_id);
        $this->assertSame('paga', $fatura->status);
        $this->assertSame('ativa', $assinatura->status);
        $this->assertSame('processado', $evento->status);
        $this->assertSame($empresa->id, $evento->empresa_id);
        $this->assertSame($assinatura->id, $evento->assinatura_id);
        $this->assertSame($fatura->id, $evento->fatura_id);
    }

    public function test_evento_sem_assinatura_correlacionada_fica_ignorado(): void
    {
        Config::set('billing.asaas.webhook_token', 'token-webhook');

        $response = $this->postJson(route('billing.webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_999',
                'customer' => 'cus_inexistente',
                'subscription' => 'sub_inexistente',
                'status' => 'OVERDUE',
            ],
        ], [
            'X-Asaas-Webhook-Token' => 'token-webhook',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'fatura_id' => null,
                'assinatura_id' => null,
            ]);

        $evento = EventoWebhook::query()->where('external_id', 'PAYMENT_OVERDUE:pay_999')->firstOrFail();
        $this->assertSame('ignorado', $evento->status);
        $this->assertSame('Evento recebido sem assinatura ou pagamento correlacionado.', $evento->erro);
    }

    public function test_webhook_tardio_nao_reabre_fatura_arquivada_por_migracao_para_plano_gratuito(): void
    {
        Config::set('billing.asaas.webhook_token', 'token-webhook');

        $planoGratuito = Plano::query()->create([
            'nome' => 'Plano Gratuito',
            'descricao' => 'Plano de entrada sem cobrança recorrente.',
            'valor_mensal' => 0,
            'limite_usuarios' => 2,
            'limite_produtos' => 100,
            'permite_promissoria' => false,
            'permite_relatorios_pdf' => false,
            'permite_exportacao_xlsx' => false,
            'ativo' => true,
        ]);

        $empresa = Empresa::query()->create([
            'nome' => 'Loja Free',
            'slug' => 'loja-free',
            'ativa' => true,
        ]);

        $assinatura = Assinatura::query()->create([
            'empresa_id' => $empresa->id,
            'plano_id' => $planoGratuito->id,
            'status' => 'ativa',
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_free_archived',
            'gateway_subscription_id' => 'sub_free_archived',
            'inicio_vigencia' => now()->subWeek()->toDateString(),
            'fim_periodo_atual' => now()->addWeeks(3)->toDateString(),
            'ativa_ate' => now()->addWeeks(3)->toDateString(),
        ]);

        $fatura = Fatura::query()->create([
            'empresa_id' => $empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_archived_123',
            'descricao' => 'Cobrança encerrada no downgrade',
            'valor' => 97,
            'vencimento' => now()->subDay()->toDateString(),
            'status' => 'cancelada',
            'checkout_url' => '',
            'invoice_url' => '',
            'payload' => [
                'local_cancellation' => [
                    'reason' => 'migrated_to_free_plan',
                    'previous_status' => 'pendente',
                ],
            ],
        ]);

        $payload = [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_archived_123',
                'customer' => 'cus_free_archived',
                'subscription' => 'sub_free_archived',
                'value' => '97.00',
                'status' => 'OVERDUE',
                'dueDate' => now()->addDay()->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_archived_123',
                'bankSlipUrl' => 'https://example.com/boleto/pay_archived_123',
            ],
        ];

        $response = $this->postJson(route('billing.webhooks.asaas'), $payload, [
            'X-Asaas-Webhook-Token' => 'token-webhook',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'fatura_id' => $fatura->id,
                'assinatura_id' => $assinatura->id,
            ]);

        $fatura->refresh();
        $assinatura->refresh();

        $this->assertSame('cancelada', $fatura->status);
        $this->assertSame('', (string) $fatura->checkout_url);
        $this->assertSame('', (string) $fatura->invoice_url);
        $this->assertSame('ativa', $assinatura->status);

        $evento = EventoWebhook::query()->where('external_id', 'PAYMENT_OVERDUE:pay_archived_123')->firstOrFail();
        $this->assertSame('processado', $evento->status);
        $this->assertSame($fatura->id, $evento->fatura_id);
    }
}