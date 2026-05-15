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
}