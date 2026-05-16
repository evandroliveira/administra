<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Produto;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanoRecursosGovernanceTest extends TestCase
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
        $this->admin = $this->criarUsuario('admin');

        $planoLite = Plano::query()->create([
            'nome' => 'Lite',
            'descricao' => 'Plano com recursos limitados',
            'valor_mensal' => 99,
            'limite_usuarios' => 1,
            'limite_produtos' => 1,
            'permite_promissoria' => false,
            'permite_relatorios_pdf' => false,
            'permite_exportacao_xlsx' => false,
            'ativo' => true,
        ]);

        Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $planoLite->id,
            'status' => 'ativa',
            'inicio_vigencia' => now()->toDateString(),
            'fim_periodo_atual' => now()->addMonth()->toDateString(),
        ]);
    }

    public function test_recurso_promissoria_bloqueia_listagem_no_plano_sem_recurso(): void
    {
        $response = $this->actingAs($this->admin)->get(route('promissorias.index'));

        $response->assertRedirect(route('assinatura.show'));
        $response->assertSessionHas('warning', 'Seu plano atual não permite operar promissórias.');
    }

    public function test_formulario_de_venda_oculta_promissoria_e_menu_no_plano_sem_recurso(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendas.create'));

        $response->assertOk()
            ->assertDontSee('value="promissoria"', false)
            ->assertDontSee('>Promissórias<', false)
            ->assertSee('Seu plano atual não permite operar promissórias.', false);
    }

    public function test_criacao_forcada_de_venda_com_promissoria_retorna_erro_de_validacao(): void
    {
        $cliente = Cliente::query()->create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Limite',
            'email' => 'cliente.limite@example.com',
            'telefone' => '(11) 99999-1111',
            'cpf_cnpj' => '12345678909',
            'endereco' => 'Rua 1',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'cep' => '80000000',
            'limite_credito' => 1000,
            'credito_disponivel' => 1000,
            'ativo' => true,
        ]);

        $produto = Produto::query()->create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'PLAN-001',
            'nome' => 'Produto Plano',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'margem_lucro' => 50,
            'custo_medio' => 10,
            'estoque_atual' => 10,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $response = $this->from(route('vendas.create'))
            ->actingAs($this->admin)
            ->post(route('vendas.store'), [
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
                'modalidade_pagamento' => 'promissoria',
                'gerar_promissoria' => true,
                'valor_entrada' => 8,
                'quantidade_parcelas' => 3,
                'intervalo_dias' => 30,
                'data_primeira_parcela' => now()->addDays(30)->toDateString(),
                'itens' => [[
                    'produto_id' => $produto->id,
                    'quantidade' => 2,
                    'preco_unitario' => 20,
                ]],
            ]);

        $response->assertRedirect(route('vendas.create'));
        $response->assertSessionHasErrors([
            'modalidade_pagamento' => 'Seu plano atual não permite operar promissórias.',
        ]);
        $this->assertDatabaseCount('promissorias', 0);
    }

    public function test_exportacao_xlsx_bloqueada_no_plano_sem_recurso(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendas.index', [
            'export' => 'xlsx',
        ]));

        $response->assertForbidden();
        $response->assertSeeText('Seu plano atual não permite exportação em XLSX.');
    }

    public function test_exportacao_pdf_bloqueada_no_plano_sem_recurso_e_central_esconde_links(): void
    {
        $this->actingAs($this->admin)
            ->get(route('relatorios.index'))
            ->assertOk()
            ->assertDontSee('Exportar XLSX', false)
            ->assertDontSee('Exportar PDF', false)
            ->assertSee('Exportar CSV', false);

        $response = $this->actingAs($this->admin)->get(route('relatorios.faturamento', [
            'data_inicio' => now()->subDays(30)->toDateString(),
            'data_fim' => now()->toDateString(),
            'export' => 'pdf',
        ]));

        $response->assertForbidden();
        $response->assertSeeText('Seu plano atual não permite exportação em PDF.');
    }

    private function criarUsuario(string $role): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'plano_'.$role.'_'.$token,
            'email' => 'plano.'.$role.'.'.$token.'@example.com',
        ]);

        $user->assignRole($role);
        $user->usuarioVendas()->create([
            'empresa_id' => $this->empresa->id,
            'perfil_id' => null,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }
}