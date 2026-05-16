<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmpresaConfiguracaoTest extends TestCase
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

    public function test_admin_visualiza_e_atualiza_dados_da_empresa_com_logo(): void
    {
        Storage::fake('public');
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);

        $this->actingAs($admin)
            ->get(route('empresa.edit'))
            ->assertOk()
            ->assertSee('Dados da Empresa', false)
            ->assertSee('Voltar para assinatura', false);

        $upload = UploadedFile::fake()->image('logo.png');

        $this->actingAs($admin)
            ->patch(route('empresa.update'), [
                'nome' => 'Administrar Prime',
                'documento' => '249.715.637-92',
                'email' => 'financeiro@example.com',
                'telefone' => '(47) 99376-637',
                'logo' => $upload,
                'remover_logo' => '0',
            ])
            ->assertRedirect(route('empresa.edit'));

        $empresaAtualizada = $this->empresa->fresh();

        $this->assertSame('Administrar Prime', $empresaAtualizada->nome);
        $this->assertSame('24971563792', $empresaAtualizada->documento);
        $this->assertSame('financeiro@example.com', $empresaAtualizada->email);
        $this->assertSame('4799376637', $empresaAtualizada->telefone);
        $this->assertNotNull($empresaAtualizada->logo);

        $logoPath = $empresaAtualizada->logo;
        Storage::disk('public')->assertExists($logoPath);

        $this->actingAs($admin)
            ->patch(route('empresa.update'), [
                'nome' => 'Administrar Prime',
                'documento' => '24971563792',
                'email' => 'financeiro@example.com',
                'telefone' => '4799376637',
                'remover_logo' => '1',
            ])
            ->assertRedirect(route('empresa.edit'));

        $empresaAtualizada->refresh();
        $this->assertNull($empresaAtualizada->logo);
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_documento_invalido_retorna_erro_de_validacao(): void
    {
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);

        $this->actingAs($admin)
            ->from(route('empresa.edit'))
            ->patch(route('empresa.update'), [
                'nome' => 'Administrar Prime',
                'documento' => '123',
                'email' => 'financeiro@example.com',
                'telefone' => '4799376637',
                'remover_logo' => '0',
            ])
            ->assertRedirect(route('empresa.edit'))
            ->assertSessionHasErrors(['documento']);

        $this->assertNotSame('Administrar Prime', $this->empresa->fresh()->nome);
    }

    public function test_telefone_invalido_retorna_erro_de_validacao(): void
    {
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);

        $this->actingAs($admin)
            ->from(route('empresa.edit'))
            ->patch(route('empresa.update'), [
                'nome' => 'Administrar Prime',
                'documento' => '24971563792',
                'email' => 'financeiro@example.com',
                'telefone' => '123',
                'remover_logo' => '0',
            ])
            ->assertRedirect(route('empresa.edit'))
            ->assertSessionHasErrors(['telefone']);

        $this->assertNotSame('Administrar Prime', $this->empresa->fresh()->nome);
    }

    public function test_vendedor_nao_acessa_configuracao_da_empresa(): void
    {
        $vendedor = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::VENDEDOR);

        $this->actingAs($vendedor)
            ->get(route('empresa.edit'))
            ->assertForbidden();
    }

    public function test_configuracao_da_empresa_permanece_acessivel_durante_regularizacao(): void
    {
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
        $plano = Plano::query()->create([
            'nome' => 'Plano Regularizacao '.Str::lower(Str::random(6)),
            'descricao' => 'Plano de teste',
            'valor_mensal' => 99,
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
            'inicio_vigencia' => Carbon::today()->toDateString(),
            'fim_periodo_atual' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->empresa->update(['ativa' => false]);

        $this->actingAs($admin)
            ->get(route('empresa.edit'))
            ->assertOk()
            ->assertSee('Dados da Empresa', false)
            ->assertSee('Status da empresa', false)
            ->assertSee('Inativa', false)
            ->assertSee('Inadimplente', false);
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));
        $perfil = Perfil::query()->where('nome', $role)->first();

        $user = User::factory()->create([
            'username' => 'empresa_'.$role.'_'.$token,
            'email' => 'empresa.'.$role.'.'.$token.'@example.com',
        ]);

        $user->assignRole($role);

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfil?->id,
            'ativo' => true,
            'data_contratacao' => Carbon::today()->toDateString(),
        ]);

        return $user;
    }
}