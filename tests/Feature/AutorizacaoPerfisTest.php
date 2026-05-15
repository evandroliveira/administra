<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutorizacaoPerfisTest extends TestCase
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

    public function test_convidado_e_redirecionado_para_login_em_rota_protegida(): void
    {
        $this->get(route('clientes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_recepcao_acessa_clientes_mas_nao_produtos_vendas_financeiro(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::RECEPCAO);

        $this->actingAs($user)->get(route('clientes.index'))->assertOk();
        $this->actingAs($user)->get(route('produtos.index'))->assertForbidden();
        $this->actingAs($user)->get(route('vendas.index'))->assertForbidden();
        $this->actingAs($user)->get(route('contas.receber.index'))->assertForbidden();
    }

    public function test_vendedor_acessa_clientes_e_vendas_mas_nao_produtos_financeiro(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::VENDEDOR);

        $this->actingAs($user)->get(route('clientes.index'))->assertOk();
        $this->actingAs($user)->get(route('vendas.index'))->assertOk();
        $this->actingAs($user)->get(route('produtos.index'))->assertForbidden();
        $this->actingAs($user)->get(route('contas.receber.index'))->assertForbidden();
    }

    public function test_gerente_acessa_todos_os_modulos_operacionais(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::GERENTE);

        $this->actingAs($user)->get(route('clientes.index'))->assertOk();
        $this->actingAs($user)->get(route('produtos.index'))->assertOk();
        $this->actingAs($user)->get(route('vendas.index'))->assertOk();
        $this->actingAs($user)->get(route('contas.receber.index'))->assertOk();
        $this->actingAs($user)->get(route('contas.pagar.index'))->assertOk();
        $this->actingAs($user)->get(route('promissorias.index'))->assertOk();
    }

    public function test_admin_acessa_todos_os_modulos_operacionais(): void
    {
        $user = $this->criarUsuarioComPerfil(Perfil::ADMIN);

        $this->actingAs($user)->get(route('clientes.index'))->assertOk();
        $this->actingAs($user)->get(route('produtos.index'))->assertOk();
        $this->actingAs($user)->get(route('vendas.index'))->assertOk();
        $this->actingAs($user)->get(route('contas.receber.index'))->assertOk();
        $this->actingAs($user)->get(route('contas.pagar.index'))->assertOk();
        $this->actingAs($user)->get(route('promissorias.index'))->assertOk();
    }

    private function criarUsuarioComPerfil(string $perfilNome): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'u_'.$perfilNome.'_'.$token,
            'email' => 'u_'.$perfilNome.'_'.$token.'@example.com',
        ]);

        $user->assignRole($perfilNome);

        $perfil = Perfil::query()->where('nome', $perfilNome)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $this->empresa->id,
            'perfil_id' => $perfil?->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }
}
