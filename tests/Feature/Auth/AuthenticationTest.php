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
use Illuminate\Support\Facades\DB;
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

    public function test_users_can_authenticate_using_username_on_login_screen(): void
    {
        $user = $this->criarUsuarioVinculado(Perfil::VENDEDOR, 'legacy_username_login');

        $response = $this->post('/login', [
            'email' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_django_password_hashes_and_are_rehashed(): void
    {
        $user = $this->criarUsuarioLegacy(999, 'legacy_admin', 'legacy-admin@system.local', $this->gerarHashDjango('admin123'));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringStartsWith('$2y$', (string) $user->fresh()->password);
    }

    public function test_users_can_authenticate_with_plain_text_legacy_passwords_and_are_rehashed(): void
    {
        $user = $this->criarUsuarioLegacy(998, 'legacy_plain', 'legacy-plain@system.local', 'teste123');

        $response = $this->post('/login', [
            'email' => $user->username,
            'password' => 'teste123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringStartsWith('$2y$', (string) $user->fresh()->password);
    }

    public function test_users_can_authenticate_with_sha1_legacy_passwords_and_are_rehashed(): void
    {
        $user = $this->criarUsuarioLegacy(997, 'legacy_sha1', 'legacy-sha1@system.local', sha1('admin123'));

        $response = $this->post('/login', [
            'email' => $user->username,
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringStartsWith('$2y$', (string) $user->fresh()->password);
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

    private function criarUsuarioLegacy(int $id, string $username, string $email, string $password): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'username' => $username,
            'name' => str_replace('_', ' ', ucfirst($username)),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail($id);
        $user->syncRoles([Perfil::ADMIN]);

        $perfil = Perfil::query()->where('nome', Perfil::ADMIN)->firstOrFail();

        UsuarioVendas::create([
            'user_id' => $user->id,
            'empresa_id' => $this->empresa->id,
            'perfil_id' => $perfil->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }

    private function gerarHashDjango(string $password, string $salt = 'legacytestsalt', int $iterations = 1000): string
    {
        $hash = base64_encode(hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true));

        return sprintf('pbkdf2_sha256$%d$%s$%s', $iterations, $salt, $hash);
    }
}
