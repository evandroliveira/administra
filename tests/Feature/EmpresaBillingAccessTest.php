<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmpresaBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected User $admin;

    protected Plano $plano;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PerfilUsuarioSeeder::class);

        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
        $this->admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
        $this->plano = Plano::query()->create([
            'nome' => 'Plano Bloqueio',
            'descricao' => 'Plano de teste',
            'valor_mensal' => 149,
            'limite_usuarios' => 5,
            'limite_produtos' => 100,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        Cliente::query()->create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Bloqueio',
            'email' => 'cliente.bloqueio@example.com',
            'telefone' => '(11) 99999-9999',
            'cpf_cnpj' => '12345678909',
            'endereco' => 'Rua Acesso',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'cep' => '80000000',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);
    }

    public function test_empresa_inadimplente_redireciona_para_assinatura_em_rota_operacional(): void
    {
        Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $this->plano->id,
            'status' => 'inadimplente',
            'inicio_vigencia' => now()->subMonth()->toDateString(),
            'fim_periodo_atual' => now()->subDay()->toDateString(),
            'ativa_ate' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('clientes.index'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A assinatura da empresa precisa de regularização para liberar o uso do sistema.');
    }

    public function test_dashboard_tambem_e_bloqueado_quando_empresa_esta_inativa(): void
    {
        $this->empresa->update([
            'ativa' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'A empresa está inativa. Regularize o cadastro para continuar.');
    }

    public function test_tela_de_assinatura_permanece_acessivel_mesmo_quando_empresa_precisa_regularizar(): void
    {
        Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $this->plano->id,
            'status' => 'suspensa',
            'inicio_vigencia' => now()->subMonth()->toDateString(),
            'fim_periodo_atual' => now()->subDays(10)->toDateString(),
            'ativa_ate' => now()->subDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('assinatura.show'));

        $response->assertOk()
            ->assertSee('Assinatura', false)
            ->assertSee('Suspensa', false);
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'acesso_'.$role.'_'.$token,
            'email' => 'acesso.'.$role.'.'.$token.'@example.com',
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