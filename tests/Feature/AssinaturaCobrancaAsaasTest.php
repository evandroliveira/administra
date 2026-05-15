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

    public function test_retorna_erro_quando_asaas_nao_esta_configurado(): void
    {
        Config::set('billing.provider', '');
        Config::set('billing.asaas.api_key', '');

        $response = $this->actingAs($this->admin)->post(route('assinatura.cobranca.store'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('error', 'Integração Asaas não configurada no ambiente.');
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