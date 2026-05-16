<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\User;
use App\Support\Billing\BillingService;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssinaturaCobrancaAsaasTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
    }

    public function test_admin_sincroniza_cobranca_asaas_e_redireciona_para_checkout_da_primeira_fatura(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');
        Config::set('billing.asaas.subscription_cycle', 'MONTHLY');

        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers' => Http::response([
                'id' => 'cus_sync_123',
            ]),
            'https://api.asaas.com/v3/subscriptions' => Http::response([
                'id' => 'sub_sync_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_sync_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_sync_123',
                    'description' => 'Assinatura Plano Padrão - Administrar',
                    'value' => '97.00',
                    'status' => 'PENDING',
                    'dueDate' => now()->toDateString(),
                    'invoiceUrl' => 'https://example.com/fatura/pay_sync_123',
                    'bankSlipUrl' => 'https://example.com/boleto/pay_sync_123',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect('https://example.com/boleto/pay_sync_123');

        $assinatura = $this->empresa->assinaturas()->with('faturas')->latest('id')->firstOrFail();
        $this->assertSame('cus_sync_123', $assinatura->gateway_customer_id);
        $this->assertSame('sub_sync_123', $assinatura->gateway_subscription_id);

        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_sync_123',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_sync_123',
        ]);

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.asaas.com/v3/subscriptions'
                && $request['billingType'] === 'UNDEFINED'
                && $request['cycle'] === 'MONTHLY'
                && $request['customer'] === 'cus_sync_123';
        });
    }

    public function test_admin_prefere_pagamento_em_aberto_com_url_quando_gateway_retorna_historico_misto(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');
        Config::set('billing.asaas.subscription_cycle', 'MONTHLY');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_hist_123',
            'gateway_subscription_id' => 'sub_hist_123',
            'status' => 'inadimplente',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_hist_123' => Http::response([
                'id' => 'cus_hist_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_hist_123' => Http::response([
                'id' => 'sub_hist_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_hist_123/payments' => Http::response([
                'data' => [
                    [
                        'id' => 'pay_hist_001',
                        'description' => 'Assinatura antiga',
                        'value' => '97.00',
                        'status' => 'RECEIVED',
                        'dueDate' => now()->subMonth()->toDateString(),
                        'invoiceUrl' => '',
                        'bankSlipUrl' => '',
                    ],
                    [
                        'id' => 'pay_hist_002',
                        'description' => 'Assinatura em aberto',
                        'value' => '97.00',
                        'status' => 'PENDING',
                        'dueDate' => now()->addDay()->toDateString(),
                        'invoiceUrl' => 'https://example.com/fatura/pay_hist_002',
                        'bankSlipUrl' => 'https://example.com/boleto/pay_hist_002',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect('https://example.com/boleto/pay_hist_002');
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_hist_002',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_hist_002',
        ]);
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_hist_001',
            'status' => 'paga',
        ]);
        Http::assertSentCount(3);
    }

    public function test_sync_nao_reaproveita_link_de_fatura_paga_quando_cobranca_atual_aberta_nao_tem_url(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_paid_123',
            'gateway_subscription_id' => 'sub_paid_123',
            'status' => 'inadimplente',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_paid_123' => Http::response([
                'id' => 'cus_paid_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_paid_123' => Http::response([
                'id' => 'sub_paid_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_paid_123/payments' => Http::response([
                'data' => [
                    [
                        'id' => 'pay_paid_old',
                        'description' => 'Cobrança já paga',
                        'value' => '97.00',
                        'status' => 'RECEIVED',
                        'dueDate' => now()->subMonth()->toDateString(),
                        'invoiceUrl' => 'https://example.com/fatura/pay_paid_old',
                        'bankSlipUrl' => 'https://example.com/boleto/pay_paid_old',
                    ],
                    [
                        'id' => 'pay_open_no_url',
                        'description' => 'Cobrança atual sem link',
                        'value' => '97.00',
                        'status' => 'OVERDUE',
                        'dueDate' => now()->subDay()->toDateString(),
                        'invoiceUrl' => '',
                        'bankSlipUrl' => '',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A cobrança foi sincronizada, mas o gateway não retornou um link de pagamento utilizável para a fatura atual.');
    Http::assertSentCount(3);
    }

    public function test_retorna_erro_quando_asaas_nao_esta_configurado(): void
    {
        Config::set('billing.provider', '');
        Config::set('billing.asaas.api_key', '');

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('error', 'Integração Asaas não configurada no ambiente.');
    }

    public function test_retorna_aviso_quando_gateway_nao_entrega_link_acionavel_para_cobranca_existente(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_empty_123',
            'gateway_subscription_id' => 'sub_empty_123',
            'status' => 'inadimplente',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_empty_123' => Http::response([
                'id' => 'cus_empty_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_empty_123' => Http::response([
                'id' => 'sub_empty_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_empty_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_empty_123',
                    'description' => 'Assinatura sem link',
                    'value' => '97.00',
                    'status' => 'PENDING',
                    'dueDate' => now()->addDay()->toDateString(),
                    'invoiceUrl' => '',
                    'bankSlipUrl' => '',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A cobrança foi sincronizada, mas o gateway não retornou um link de pagamento utilizável para a fatura atual.');
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_empty_123',
            'status' => 'pendente',
            'checkout_url' => '',
        ]);
        Http::assertSentCount(3);
    }

    public function test_admin_altera_plano_pago_e_atualiza_assinatura_existente_no_asaas(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');
        Config::set('billing.asaas.subscription_cycle', 'MONTHLY');

        $planoEscala = Plano::query()->create([
            'nome' => 'Plano Escala',
            'descricao' => 'Mais capacidade para a operação comercial.',
            'valor_mensal' => 197,
            'limite_usuarios' => 12,
            'limite_produtos' => 5000,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_plan_123',
            'gateway_subscription_id' => 'sub_plan_123',
            'status' => 'ativa',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_plan_123' => Http::response([
                'id' => 'cus_plan_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_plan_123' => Http::response([
                'id' => 'sub_plan_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_plan_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_plan_123',
                    'description' => 'Assinatura Plano Escala - Administrar',
                    'value' => '197.00',
                    'status' => 'PENDING',
                    'dueDate' => now()->toDateString(),
                    'invoiceUrl' => 'https://example.com/fatura/pay_plan_123',
                    'bankSlipUrl' => 'https://example.com/boleto/pay_plan_123',
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.plano.update'), [
            'plano_id' => $planoEscala->id,
        ]);

        $response->assertRedirect('https://example.com/boleto/pay_plan_123');

        $assinatura->refresh();
        $this->assertSame($planoEscala->id, $assinatura->plano_id);
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_plan_123',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_plan_123',
        ]);

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.asaas.com/v3/subscriptions/sub_plan_123'
                && (float) $request['value'] === 197.0
                && $request['status'] === 'ACTIVE'
                && $request['updatePendingPayments'] === true;
        });
    }

    public function test_regenera_link_atualizando_fatura_em_aberto_sem_url(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_regen_123',
            'gateway_subscription_id' => 'sub_regen_123',
            'status' => 'inadimplente',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_regen_123' => Http::response([
                'id' => 'cus_regen_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_regen_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_regen_123',
                    'description' => 'Cobrança vencida sem url',
                    'value' => '97.00',
                    'status' => 'OVERDUE',
                    'dueDate' => now()->subDay()->toDateString(),
                    'invoiceUrl' => '',
                    'bankSlipUrl' => '',
                ]],
            ]),
            'https://api.asaas.com/v3/payments/pay_regen_123' => Http::response([
                'id' => 'pay_regen_123',
                'description' => 'Cobrança vencida sem url',
                'value' => '97.00',
                'status' => 'PENDING',
                'dueDate' => now()->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_regen_123',
                'bankSlipUrl' => 'https://example.com/boleto/pay_regen_123',
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.regenerate'));

        $response->assertRedirect('https://example.com/boleto/pay_regen_123');
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_regen_123',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_regen_123',
        ]);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.asaas.com/v3/payments/pay_regen_123');
    }

    public function test_regenera_cobranca_criando_novo_payment_quando_nao_existe_fatura_em_aberto_reaproveitavel(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '(44) 99837-7255',
        ]);
        $assinatura->update([
            'gateway_customer_id' => 'cus_newpay_123',
            'gateway_subscription_id' => 'sub_newpay_123',
            'status' => 'inadimplente',
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers/cus_newpay_123' => Http::response([
                'id' => 'cus_newpay_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_newpay_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_paid_only_123',
                    'description' => 'Cobrança antiga paga',
                    'value' => '97.00',
                    'status' => 'RECEIVED',
                    'dueDate' => now()->subMonth()->toDateString(),
                    'invoiceUrl' => 'https://example.com/fatura/pay_paid_only_123',
                    'bankSlipUrl' => 'https://example.com/boleto/pay_paid_only_123',
                ]],
            ]),
            'https://api.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_new_123',
                'description' => 'Recuperação da assinatura Plano Padrão - Administrar',
                'value' => '97.00',
                'status' => 'PENDING',
                'dueDate' => now()->toDateString(),
                'invoiceUrl' => 'https://example.com/fatura/pay_new_123',
                'bankSlipUrl' => 'https://example.com/boleto/pay_new_123',
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.regenerate'));

        $response->assertRedirect('https://example.com/boleto/pay_new_123');
        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_new_123',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_new_123',
        ]);
        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.asaas.com/v3/payments'
                && $request['customer'] === 'cus_newpay_123'
                && $request['billingType'] === 'UNDEFINED';
        });
    }

    public function test_documento_invalido_bloqueia_sincronizacao_antes_do_gateway(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        $this->empresa->update([
            'documento' => '6958875452',
            'telefone' => '(44) 99837-7255',
        ]);

        Http::fake();

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('error', function (string $message): bool {
            return str_contains($message, 'CPF ou CNPJ válido');
        });

        Http::assertNothingSent();
    }

    public function test_telefone_invalido_bloqueia_sincronizacao_antes_do_gateway(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        $this->empresa->update([
            'documento' => '24971563792',
            'telefone' => '123',
        ]);

        Http::fake();

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('error', function (string $message): bool {
            return str_contains($message, 'telefone válido');
        });

        Http::assertNothingSent();
    }

    public function test_plano_gratuito_ativa_assinatura_sem_criar_cobranca_recorrente(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');

        $assinatura = app(BillingService::class)->ensureCurrentSubscription($this->empresa);
        $planoGratis = Plano::query()->create([
            'nome' => 'Plano Gratuito',
            'descricao' => 'Plano free',
            'valor_mensal' => 0,
            'limite_usuarios' => 2,
            'limite_produtos' => 20,
            'permite_promissoria' => false,
            'permite_relatorios_pdf' => false,
            'permite_exportacao_xlsx' => false,
            'ativo' => true,
        ]);

        $assinatura->update([
            'plano_id' => $planoGratis->id,
            'status' => 'teste',
            'ativa_ate' => now()->addDays(7)->toDateString(),
            'fim_periodo_atual' => now()->addDays(7)->toDateString(),
            'gateway_customer_id' => null,
            'gateway_subscription_id' => null,
        ]);

        Http::fake([
            'https://api.asaas.com/v3/customers' => Http::response([
                'id' => 'cus_free_123',
            ]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('status', 'A assinatura foi ativada no plano gratuito, sem necessidade de gateway.');

        $assinatura->refresh();
        $this->assertSame('ativa', $assinatura->status);
        $this->assertSame('cus_free_123', $assinatura->gateway_customer_id);
        $this->assertNull($assinatura->gateway_subscription_id);
        $this->assertDatabaseCount('faturas', 0);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.asaas.com/v3/customers');
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'asaas_'.$role.'_'.$token,
            'email' => 'asaas.'.$role.'.'.$token.'@example.com',
        ]);

        $user->assignRole($role);

        $perfil = Perfil::query()->where('nome', $role)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil?->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }
}