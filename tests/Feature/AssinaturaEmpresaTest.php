<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Fatura;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssinaturaEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PerfilUsuarioSeeder::class);
        $this->empresa = Empresa::query()->where('slug', 'administrar')->firstOrFail();
    }

    public function test_admin_visualiza_pagina_de_assinatura_e_assinatura_padrao_e_criada_automaticamente(): void
    {
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);

        $response = $this->actingAs($admin)->get(route('assinatura.show'));

        $response->assertOk()
            ->assertSee('Assinatura', false)
            ->assertSee('Plano Padrão', false)
            ->assertSee('Faturas Recentes', false)
            ->assertSee('Plano e Recursos', false)
            ->assertSee('modo local', false);

        $plano = Plano::query()->where('nome', 'Plano Padrão')->first();
        $this->assertNotNull($plano);

        $this->assertDatabaseHas('assinaturas', [
            'empresa_id' => $this->empresa->id,
            'plano_id' => $plano->id,
            'status' => 'ativa',
        ]);
    }

    public function test_faturas_da_empresa_sao_exibidas_com_escopo_correto(): void
    {
        $admin = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::ADMIN);
        $plano = Plano::query()->create([
            'nome' => 'Plano Escopo',
            'descricao' => 'Plano de teste',
            'valor_mensal' => 99,
            'limite_usuarios' => 5,
            'limite_produtos' => 1000,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        $assinaturaEmpresa = Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $plano->id,
            'status' => 'ativa',
            'inicio_vigencia' => now()->toDateString(),
            'fim_periodo_atual' => now()->addMonth()->toDateString(),
        ]);

        Fatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'assinatura_id' => $assinaturaEmpresa->id,
            'external_id' => 'fat-empresa',
            'descricao' => 'Assinatura recorrente empresa',
            'valor' => 97,
            'vencimento' => now()->addDays(5)->toDateString(),
            'status' => 'pendente',
            'checkout_url' => 'https://checkout.exemplo.local/fat-empresa',
        ]);

        $outraEmpresa = Empresa::query()->create([
            'nome' => 'Outra Empresa Billing',
            'slug' => 'outra-empresa-billing',
            'ativa' => true,
        ]);
        $assinaturaOutra = Assinatura::query()->create([
            'empresa_id' => $outraEmpresa->id,
            'plano_id' => $plano->id,
            'status' => 'ativa',
            'inicio_vigencia' => now()->toDateString(),
            'fim_periodo_atual' => now()->addMonth()->toDateString(),
        ]);
        Fatura::query()->create([
            'empresa_id' => $outraEmpresa->id,
            'assinatura_id' => $assinaturaOutra->id,
            'external_id' => 'fat-outra',
            'descricao' => 'Assinatura outra empresa',
            'valor' => 120,
            'vencimento' => now()->addDays(7)->toDateString(),
            'status' => 'pendente',
        ]);

        $this->actingAs($admin)
            ->get(route('assinatura.show'))
            ->assertOk()
            ->assertSee('fat-empresa', false)
            ->assertSee('Assinatura recorrente empresa', false)
            ->assertDontSee('fat-outra', false)
            ->assertDontSee('Assinatura outra empresa', false);
    }

    public function test_vendedor_visualiza_resumo_sem_bloco_administrativo(): void
    {
        $vendedor = $this->criarUsuarioDaEmpresa($this->empresa, Perfil::VENDEDOR);

        $this->actingAs($vendedor)
            ->get(route('assinatura.show'))
            ->assertOk()
            ->assertSee('Resumo da Assinatura', false)
            ->assertDontSee('Plano e Recursos', false);
    }

    private function criarUsuarioDaEmpresa(Empresa $empresa, string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'billing_'.$role.'_'.$token,
            'email' => 'billing.'.$role.'.'.$token.'@example.com',
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