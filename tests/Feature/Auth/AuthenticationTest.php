<?php

namespace Tests\Feature\Auth;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\User;
use App\Models\UsuarioVendas;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PerfilUsuarioSeeder::class);
        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('retomar o pagamento na tela de assinatura', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->criarUsuarioVinculado(Perfil::VENDEDOR, 'auth_user');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_of_inactive_company_can_authenticate_and_are_redirected_to_subscription_regularization(): void
    {
        $user = $this->criarUsuarioVinculado(Perfil::ADMIN, 'auth_inactive_company');
        $this->empresa->update(['ativa' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A empresa está inativa. Regularize o cadastro para continuar.');
    }

    public function test_users_with_subscription_needing_regularization_are_redirected_to_subscription_page_after_login(): void
    {
        $user = $this->criarUsuarioVinculado(Perfil::ADMIN, 'auth_regularizacao');
        $plano = Plano::query()->create([
            'nome' => 'Plano Login Regularizacao',
            'descricao' => 'Plano de teste',
            'valor_mensal' => 97,
            'limite_usuarios' => 5,
            'limite_produtos' => 100,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $plano->id,
            'status' => 'inadimplente',
            'inicio_vigencia' => now()->subMonth()->toDateString(),
            'fim_periodo_atual' => now()->subDay()->toDateString(),
            'ativa_ate' => now()->subDay()->toDateString(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A assinatura da empresa precisa de regularização para liberar o uso do sistema.');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    private function criarUsuarioVinculado(string $perfilNome, string $username): User
    {
        $user = User::factory()->create([
            'username' => $username,
        ]);
        $user->syncRoles([$perfilNome]);

        $perfil = Perfil::query()->where('nome', $perfilNome)->firstOrFail();

        UsuarioVendas::create([
            'user_id' => $user->id,
            'empresa_id' => $this->empresa->id,
            'perfil_id' => $perfil->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }
}
