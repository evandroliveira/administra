<?php

namespace Tests\Feature\Auth;

use App\Models\Perfil;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('Criar empresa', false);
        $response->assertSee('Primeiro administrador', false);
    }

    public function test_new_users_can_register_publicly_and_create_company(): void
    {
        Storage::fake('public');

        $response = $this->post('/register', [
            'nome_empresa' => 'Loja Solar',
            'documento' => '249.715.637-92',
            'email_empresa' => '',
            'telefone_empresa' => '(44) 99837-7255',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'username' => 'loja.solar.admin',
            'name' => 'Maria Solar',
            'email' => 'maria@solar.example',
            'password' => 'Password!123',
            'password_confirmation' => 'Password!123',
        ]);

        $response->assertRedirect(route('assinatura.show'));

        $user = User::query()->where('email', 'maria@solar.example')->firstOrFail();
        $empresa = $user->usuarioVendas->empresa;
        $assinatura = $empresa->assinaturas()->with('plano')->latest('id')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(Perfil::ADMIN));
        $this->assertSame('Loja Solar', $empresa->nome);
        $this->assertSame('loja-solar', $empresa->slug);
        $this->assertSame('24971563792', $empresa->documento);
        $this->assertSame('maria@solar.example', $empresa->email);
        $this->assertSame('44998377255', $empresa->telefone);
        $this->assertNotNull($empresa->logo);
        $this->assertSame('ativa', $assinatura->status);
        $this->assertSame('Plano Padrão', $assinatura->plano->nome);

        Storage::disk('public')->assertExists($empresa->logo);

        $this->assertDatabaseHas('usuario_vendas', [
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'ativo' => true,
        ]);
    }

    public function test_registration_requires_document_when_paid_billing_is_configured(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');

        $response = $this->from('/register')->post('/register', [
            'nome_empresa' => 'Loja Sem Documento',
            'documento' => '',
            'email_empresa' => '',
            'telefone_empresa' => '(44) 99837-7255',
            'username' => 'sem.documento',
            'name' => 'Carlos Documento',
            'email' => 'carlos@documento.example',
            'password' => 'Password!123',
            'password_confirmation' => 'Password!123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['documento']);
        $this->assertGuest();
    }

    public function test_registration_rejects_invalid_phone(): void
    {
        $response = $this->from('/register')->post('/register', [
            'nome_empresa' => 'Loja Telefone Invalido',
            'documento' => '249.715.637-92',
            'email_empresa' => '',
            'telefone_empresa' => '123',
            'username' => 'telefone.invalido',
            'name' => 'Beatriz Telefone',
            'email' => 'beatriz@telefone.example',
            'password' => 'Password!123',
            'password_confirmation' => 'Password!123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['telefone_empresa']);
        $this->assertGuest();
    }

    public function test_registration_redirects_to_checkout_when_billing_is_configured(): void
    {
        Config::set('billing.provider', 'asaas');
        Config::set('billing.asaas.api_key', 'token-teste');
        Config::set('billing.asaas.base_url', 'https://api.asaas.com/v3');
        Config::set('billing.asaas.billing_type', 'UNDEFINED');
        Config::set('billing.asaas.subscription_cycle', 'MONTHLY');

        Http::fake([
            'https://api.asaas.com/v3/customers' => Http::response([
                'id' => 'cus_reg_123',
            ]),
            'https://api.asaas.com/v3/subscriptions' => Http::response([
                'id' => 'sub_reg_123',
            ]),
            'https://api.asaas.com/v3/subscriptions/sub_reg_123/payments' => Http::response([
                'data' => [[
                    'id' => 'pay_reg_123',
                    'description' => 'Assinatura Plano Padrão - Loja Checkout',
                    'value' => '97.00',
                    'status' => 'PENDING',
                    'dueDate' => now()->toDateString(),
                    'invoiceUrl' => 'https://example.com/fatura/pay_reg_123',
                    'bankSlipUrl' => 'https://example.com/boleto/pay_reg_123',
                ]],
            ]),
        ]);

        $response = $this->post('/register', [
            'nome_empresa' => 'Loja Checkout',
            'documento' => '249.715.637-92',
            'email_empresa' => 'financeiro@checkout.example',
            'telefone_empresa' => '(44) 99837-7255',
            'username' => 'checkout.admin',
            'name' => 'Ana Checkout',
            'email' => 'ana@checkout.example',
            'password' => 'Password!123',
            'password_confirmation' => 'Password!123',
        ]);

        $response->assertRedirect('https://example.com/boleto/pay_reg_123');

        $user = User::query()->where('email', 'ana@checkout.example')->firstOrFail();
        $empresa = $user->usuarioVendas->empresa;
        $assinatura = $empresa->assinaturas()->latest('id')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('inadimplente', $assinatura->status);
        $this->assertSame('cus_reg_123', $assinatura->gateway_customer_id);
        $this->assertSame('sub_reg_123', $assinatura->gateway_subscription_id);

        $this->assertDatabaseHas('faturas', [
            'empresa_id' => $empresa->id,
            'assinatura_id' => $assinatura->id,
            'external_id' => 'pay_reg_123',
            'status' => 'pendente',
            'checkout_url' => 'https://example.com/boleto/pay_reg_123',
        ]);
    }

    public function test_registration_bootstraps_missing_profiles_and_roles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        Role::query()->delete();
        Permission::query()->delete();
        Perfil::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->post('/register', [
            'nome_empresa' => 'Loja Sem Seeder',
            'documento' => '249.715.637-92',
            'email_empresa' => '',
            'telefone_empresa' => '(44) 99837-7255',
            'username' => 'sem.seeder.admin',
            'name' => 'Paula Seeder',
            'email' => 'paula@seeder.example',
            'password' => 'Password!123',
            'password_confirmation' => 'Password!123',
        ]);

        $response->assertRedirect(route('assinatura.show'));

        $user = User::query()->where('email', 'paula@seeder.example')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(Perfil::ADMIN));
        $this->assertDatabaseHas('perfis', ['nome' => Perfil::ADMIN]);
        $this->assertDatabaseHas('perfis', ['nome' => Perfil::GERENTE]);
        $this->assertDatabaseHas('perfis', ['nome' => Perfil::VENDEDOR]);
        $this->assertDatabaseHas('perfis', ['nome' => Perfil::RECEPCAO]);
        $this->assertDatabaseHas('roles', ['name' => Perfil::ADMIN, 'guard_name' => 'web']);
    }
}
