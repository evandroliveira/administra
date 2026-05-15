<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioVendas;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class UsuariosEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);
        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
    }

    public function test_admin_lista_apenas_usuarios_da_propria_empresa_e_cadastra_novo_usuario(): void
    {
        $admin = $this->criarUsuarioComPerfil(Perfil::ADMIN, $this->empresa, 'admin_eq');

        $empresaExterna = Empresa::create([
            'nome' => 'Empresa Externa',
            'slug' => 'empresa-externa',
            'documento' => '123123123000199',
            'email' => 'externa@example.com',
            'telefone' => '1133330000',
            'ativa' => true,
        ]);
        $usuarioExterno = $this->criarUsuarioComPerfil(Perfil::VENDEDOR, $empresaExterna, 'externo');

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Usuários da Empresa', false)
            ->assertSee($admin->email, false)
            ->assertDontSee($usuarioExterno->email, false);

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'username' => 'gerente_novo',
                'name' => 'Gerente Novo',
                'email' => 'gerente.novo@example.com',
                'perfil' => Perfil::GERENTE,
                'telefone' => '11988887777',
                'endereco' => 'Rua Time',
                'cidade' => 'Sao Paulo',
                'estado' => 'SP',
                'cep' => '01000000',
                'ativo' => '1',
                'password' => 'senhaforte123',
                'password_confirmation' => 'senhaforte123',
            ])
            ->assertRedirect(route('usuarios.index'));

        $novoUsuario = User::query()->where('username', 'gerente_novo')->first();

        $this->assertNotNull($novoUsuario);
        $this->assertTrue($novoUsuario->hasRole(Perfil::GERENTE));
        $this->assertDatabaseHas('usuario_vendas', [
            'user_id' => $novoUsuario->id,
            'empresa_id' => $this->empresa->id,
            'ativo' => true,
        ]);
    }

    public function test_vendedor_nao_acessa_gestao_de_usuarios(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::VENDEDOR, $this->empresa, 'vend_eq');

        $this->actingAs($user)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_edicao_bloqueia_desativacao_do_ultimo_admin(): void
    {
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $perfilAdmin = Perfil::query()->where('nome', Perfil::ADMIN)->firstOrFail();
        $usuarioAdmin = $admin->usuarioVendas()->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('usuarios.edit', $usuarioAdmin))
            ->patch(route('usuarios.update', $usuarioAdmin), [
                'username' => 'admin',
                'name' => 'Administrador Sistema',
                'email' => 'admin@system.local',
                'perfil' => $perfilAdmin->nome,
                'telefone' => '',
                'endereco' => '',
                'cidade' => '',
                'estado' => '',
                'cep' => '',
                'ativo' => '0',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('usuarios.edit', $usuarioAdmin));
        $response->assertSessionHasErrors('perfil');

        $usuarioAdmin->refresh();
        $this->assertTrue($usuarioAdmin->ativo);
    }

    public function test_register_publico_nao_esta_disponivel_e_usuario_inativo_nao_autentica(): void
    {
        $this->get('/register')->assertNotFound();

        $user = $this->criarUsuarioComPerfil(Perfil::VENDEDOR, $this->empresa, 'inativo_login');
        $user->usuarioVendas()->update(['ativo' => false]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function criarUsuarioComPerfil(string $perfilNome, Empresa $empresa, string $prefixo): User
    {
        $token = Str::lower(Str::random(6));
        $perfil = Perfil::query()->where('nome', $perfilNome)->firstOrFail();

        $user = User::factory()->create([
            'username' => $prefixo.'_'.$token,
            'email' => $prefixo.'_'.$token.'@example.com',
        ]);

        $user->syncRoles([$perfilNome]);

        UsuarioVendas::create([
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil->id,
            'ativo' => true,
            'data_contratacao' => Carbon::today()->toDateString(),
        ]);

        return $user->refresh();
    }
}