<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\Perfil;
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

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'username' => 'auth_user',
        ]);
        $user->syncRoles([Perfil::VENDEDOR]);

        $perfil = Perfil::query()->where('nome', Perfil::VENDEDOR)->firstOrFail();

        UsuarioVendas::create([
            'user_id' => $user->id,
            'empresa_id' => $this->empresa->id,
            'perfil_id' => $perfil->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
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
}
