<?php

namespace Tests\Feature;

use App\Models\Assinatura;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimentacaoEstoque;
use App\Models\Perfil;
use App\Models\Plano;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\User;
use Database\Seeders\PerfilUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CadastrosCrudTest extends TestCase
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
        $this->admin = $this->criarUsuarioAdminDaEmpresa($this->empresa);
    }

    public function test_cliente_crud_fluxo_basico(): void
    {
        $payloadStore = $this->payloadCliente([
            'email' => 'cliente.crud@example.com',
            'cpf_cnpj' => '44444444444',
            'nome' => 'Cliente CRUD',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payloadStore)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $cliente = Cliente::query()->where('email', 'cliente.crud@example.com')->firstOrFail();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'empresa_id' => $this->empresa->id,
            'nome' => 'Cliente CRUD',
        ]);

        $payloadUpdate = $this->payloadCliente([
            'nome' => 'Cliente CRUD Alterado',
            'email' => 'cliente.crud@example.com',
            'cpf_cnpj' => '44444444444',
            'limite_credito' => 1500,
        ]);

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente), $payloadUpdate)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'Cliente CRUD Alterado',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_cliente_bloqueia_email_duplicado_na_mesma_empresa(): void
    {
        Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Base',
            'email' => 'repetido@example.com',
            'telefone' => '(11) 91111-1111',
            'cpf_cnpj' => '55555555555',
            'endereco' => 'Rua Base',
            'numero' => '10',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 500,
            'credito_disponivel' => 500,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $payload = $this->payloadCliente([
            'email' => 'repetido@example.com',
            'cpf_cnpj' => '66666666666',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payload)
            ->assertSessionHasErrors(['email']);
    }

    public function test_cliente_permite_email_repetido_em_empresa_diferente(): void
    {
        $empresaSecundaria = Empresa::create([
            'nome' => 'Empresa Secundaria',
            'slug' => 'empresa-secundaria-'.Str::lower(Str::random(6)),
            'email' => 'empresa.secundaria@example.com',
            'ativa' => true,
        ]);

        Cliente::create([
            'empresa_id' => $empresaSecundaria->id,
            'tipo' => 'PF',
            'nome' => 'Cliente Outra Empresa',
            'email' => 'compartilhado@example.com',
            'telefone' => '(11) 92222-2222',
            'cpf_cnpj' => '77777777777',
            'endereco' => 'Rua Outra',
            'numero' => '20',
            'bairro' => 'Centro',
            'cidade' => 'Campinas',
            'estado' => 'SP',
            'cep' => '13000000',
            'limite_credito' => 700,
            'credito_disponivel' => 700,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ]);

        $payload = $this->payloadCliente([
            'email' => 'compartilhado@example.com',
            'cpf_cnpj' => '88888888888',
        ]);

        $this->actingAs($this->admin)
            ->post(route('clientes.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'empresa_id' => $this->empresa->id,
            'email' => 'compartilhado@example.com',
        ]);
    }

    public function test_formulario_de_cliente_exibe_cep_antes_do_endereco_e_prepara_autopreenchimento(): void
    {
        $this->actingAs($this->admin)
            ->get(route('clientes.create'))
            ->assertOk()
            ->assertSeeInOrder(['<label for="cep"', '<label for="endereco"'], false)
            ->assertSee('data-cep-form', false)
            ->assertSee('data-cep-input', false)
            ->assertSee('data-cep-endereco', false)
            ->assertSee('data-cep-bairro', false)
            ->assertSee('data-cep-cidade', false)
            ->assertSee('data-cep-estado', false)
            ->assertSee('data-cep-status', false);
    }

    public function test_produto_crud_fluxo_basico_com_calculo_de_margem(): void
    {
        $payloadStore = $this->payloadProduto([
            'codigo' => 'SKU-CRUD-01',
            'nome' => 'Produto CRUD',
            'preco_custo' => 10,
            'preco_venda' => 20,
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payloadStore)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $produto = Produto::query()->where('codigo', 'SKU-CRUD-01')->firstOrFail();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'empresa_id' => $this->empresa->id,
            'margem_lucro' => 50,
        ]);

        $payloadUpdate = $this->payloadProduto([
            'codigo' => 'SKU-CRUD-01',
            'nome' => 'Produto CRUD Alterado',
            'preco_custo' => 20,
            'preco_venda' => 30,
        ]);

        $this->actingAs($this->admin)
            ->put(route('produtos.update', $produto), $payloadUpdate)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Produto CRUD Alterado',
            'margem_lucro' => 33.33,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('produtos.destroy', $produto))
            ->assertRedirect(route('produtos.index'));

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    public function test_produto_create_exibe_atalho_para_modal_de_categoria(): void
    {
        $this->actingAs($this->admin)
            ->get(route('produtos.create'))
            ->assertOk()
            ->assertSee('Nova categoria', false)
            ->assertSee('Cadastre a categoria sem sair do produto.', false)
            ->assertSee(route('categorias.store'), false);
    }

    public function test_categoria_pode_ser_criada_pelo_endpoint_do_modal_de_produto(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('categorias.store'), [
                'nome' => 'Acessorios',
                'descricao' => 'Categoria criada pelo modal.',
                'ativo' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Categoria criada com sucesso.')
            ->assertJsonPath('categoria.nome', 'Acessorios')
            ->assertJsonPath('categoria.descricao', 'Categoria criada pelo modal.');

        $this->assertDatabaseHas('categorias', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'Acessorios',
            'descricao' => 'Categoria criada pelo modal.',
            'ativo' => true,
        ]);
    }

    public function test_categoria_crud_fluxo_basico(): void
    {
        $this->actingAs($this->admin)
            ->post(route('categorias.store'), [
                'nome' => 'Categoria CRUD',
                'descricao' => 'Descricao inicial',
                'ativo' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categorias.index'));

        $categoria = Categoria::query()->where('nome', 'Categoria CRUD')->firstOrFail();

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Descricao inicial',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('categorias.update', $categoria), [
                'nome' => 'Categoria CRUD Atualizada',
                'descricao' => 'Descricao atualizada',
                'ativo' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Categoria CRUD Atualizada',
            'descricao' => 'Descricao atualizada',
            'ativo' => false,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_categoria_com_produtos_vinculados_e_inativada_ao_excluir(): void
    {
        $categoria = Categoria::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Categoria Em Uso',
            'descricao' => 'Nao deve ser apagada.',
            'ativo' => true,
        ]);

        $produto = Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-CAT-USO',
            'nome' => 'Produto Vinculado',
            'categoria_id' => $categoria->id,
            'preco_custo' => 10,
            'preco_venda' => 18,
            'margem_lucro' => 44.44,
            'custo_medio' => 10,
            'estoque_atual' => 3,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('status', 'Categoria possui produtos vinculados e foi inativada em vez de ser excluida.');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'ativo' => false,
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'categoria_id' => $categoria->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('produtos.edit', $produto))
            ->assertOk()
            ->assertSee('Categoria Em Uso (inativa)', false);
    }

    public function test_categoria_bloqueia_nome_duplicado_na_mesma_empresa(): void
    {
        Categoria::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Categoria Repetida',
            'descricao' => 'Base',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('categorias.store'), [
                'nome' => 'Categoria Repetida',
                'descricao' => 'Duplicada',
                'ativo' => true,
            ])
            ->assertSessionHasErrors(['nome']);
    }

    public function test_categoria_permite_nome_repetido_em_empresa_diferente(): void
    {
        $empresaSecundaria = Empresa::create([
            'nome' => 'Empresa Categoria',
            'slug' => 'empresa-categoria-'.Str::lower(Str::random(6)),
            'email' => 'empresa.categoria@example.com',
            'ativa' => true,
        ]);

        Categoria::create([
            'empresa_id' => $empresaSecundaria->id,
            'nome' => 'Categoria Compartilhada',
            'descricao' => 'Outra empresa',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('categorias.store'), [
                'nome' => 'Categoria Compartilhada',
                'descricao' => 'Empresa atual',
                'ativo' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'Categoria Compartilhada',
            'descricao' => 'Empresa atual',
        ]);
    }

    public function test_tela_de_categorias_exibe_lista_e_atalhos_principais(): void
    {
        Categoria::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Bazar',
            'descricao' => 'Itens diversos',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Categorias', false)
            ->assertSee('Nova categoria', false)
            ->assertSee('Bazar', false)
            ->assertSee('Editar', false)
            ->assertSee('Excluir', false);
    }

    public function test_produtos_podem_ser_filtrados_por_categoria_na_listagem(): void
    {
        $categoriaA = Categoria::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Audio',
            'descricao' => 'Itens de audio',
            'ativo' => true,
        ]);

        $categoriaB = Categoria::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Video',
            'descricao' => 'Itens de video',
            'ativo' => true,
        ]);

        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-AUD-01',
            'nome' => 'Caixa de Som',
            'categoria_id' => $categoriaA->id,
            'preco_custo' => 10,
            'preco_venda' => 30,
            'margem_lucro' => 66.67,
            'custo_medio' => 10,
            'estoque_atual' => 5,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-VID-01',
            'nome' => 'Projetor',
            'categoria_id' => $categoriaB->id,
            'preco_custo' => 15,
            'preco_venda' => 40,
            'margem_lucro' => 62.5,
            'custo_medio' => 15,
            'estoque_atual' => 7,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('produtos.index', ['categoria_id' => $categoriaA->id]))
            ->assertOk()
            ->assertSee('Caixa de Som', false)
            ->assertDontSee('Projetor', false);
    }

    public function test_produto_show_exibe_galeria_e_movimentacoes_importadas(): void
    {
        $produto = Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-GAL-01',
            'nome' => 'Produto Galeria',
            'preco_custo' => 15,
            'preco_venda' => 40,
            'margem_lucro' => 62.5,
            'custo_medio' => 15,
            'estoque_atual' => 5,
            'estoque_minimo' => 1,
            'ativo' => true,
        ]);

        ProdutoImagem::create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $produto->id,
            'imagem' => 'produtos/galeria/legado-galeria.png',
            'ordem' => 1,
        ]);

        MovimentacaoEstoque::create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $produto->id,
            'tipo' => 'ajuste_entrada',
            'quantidade' => 5,
            'estoque_anterior' => 0,
            'estoque_posterior' => 5,
            'custo_unitario' => 15,
            'custo_medio_anterior' => 0,
            'custo_medio_posterior' => 15,
            'origem_tipo' => null,
            'origem_id' => null,
            'observacao' => 'Carga inicial do legado.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('produtos.show', $produto))
            ->assertOk()
            ->assertSee('Imagens adicionais', false)
            ->assertSee('produtos/galeria/legado-galeria.png', false)
            ->assertSee('Movimentacoes de estoque', false)
            ->assertSee('Carga inicial do legado.', false);
    }

    public function test_produto_bloqueia_cadastro_quando_limite_do_plano_foi_atingido(): void
    {
        $this->atribuirPlanoComLimiteProdutos(1);

        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-LIM-01',
            'nome' => 'Produto Limite',
            'preco_custo' => 10,
            'preco_venda' => 25,
            'margem_lucro' => 60,
            'custo_medio' => 10,
            'estoque_atual' => 20,
            'estoque_minimo' => 3,
            'ativo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('produtos.create'))
            ->post(route('produtos.store'), $this->payloadProduto([
                'codigo' => 'SKU-LIM-02',
                'nome' => 'Produto Excedente',
            ]));

        $response->assertRedirect(route('produtos.create'));
        $response->assertSessionHasErrors([
            'codigo' => 'O plano atual permite até 1 produto(s). Faça upgrade para cadastrar mais itens.',
        ]);

        $this->assertDatabaseMissing('produtos', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-LIM-02',
        ]);
    }

    public function test_telas_de_produtos_exibem_consumo_do_plano_e_bloqueio_visual_quando_limite_foi_atingido(): void
    {
        $this->atribuirPlanoComLimiteProdutos(1);

        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-LIM-UI',
            'nome' => 'Produto UI',
            'preco_custo' => 10,
            'preco_venda' => 25,
            'margem_lucro' => 60,
            'custo_medio' => 10,
            'estoque_atual' => 20,
            'estoque_minimo' => 3,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('produtos.index'))
            ->assertOk()
            ->assertSee('Produtos cadastrados: 1 / 1.', false)
            ->assertSee('Restam 0 vaga(s) no plano atual.', false)
            ->assertSee('Gerenciar plano', false)
            ->assertDontSee('Novo produto', false);

        $this->actingAs($this->admin)
            ->get(route('produtos.create'))
            ->assertOk()
            ->assertSee('Produtos cadastrados: 1 / 1.', false)
            ->assertSee('O plano atual permite até 1 produto(s). Faça upgrade para cadastrar mais itens.', false)
            ->assertSee('Gerenciar plano', false)
            ->assertDontSee('Salvar', false);
    }

    public function test_produto_bloqueia_codigo_duplicado_na_mesma_empresa(): void
    {
        Produto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-DUP-01',
            'nome' => 'Produto Base',
            'preco_custo' => 10,
            'preco_venda' => 25,
            'margem_lucro' => 60,
            'custo_medio' => 10,
            'estoque_atual' => 20,
            'estoque_minimo' => 3,
            'ativo' => true,
        ]);

        $payload = $this->payloadProduto([
            'codigo' => 'SKU-DUP-01',
            'nome' => 'Produto Novo',
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payload)
            ->assertSessionHasErrors(['codigo']);
    }

    public function test_produto_permite_codigo_repetido_em_empresa_diferente(): void
    {
        $empresaSecundaria = Empresa::create([
            'nome' => 'Empresa Produto',
            'slug' => 'empresa-produto-'.Str::lower(Str::random(6)),
            'email' => 'empresa.produto@example.com',
            'ativa' => true,
        ]);

        Produto::create([
            'empresa_id' => $empresaSecundaria->id,
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Outra Empresa',
            'preco_custo' => 10,
            'preco_venda' => 22,
            'margem_lucro' => 54.55,
            'custo_medio' => 10,
            'estoque_atual' => 15,
            'estoque_minimo' => 2,
            'ativo' => true,
        ]);

        $payload = $this->payloadProduto([
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Local',
        ]);

        $this->actingAs($this->admin)
            ->post(route('produtos.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'SKU-COMP-01',
            'nome' => 'Produto Local',
        ]);
    }

    private function criarUsuarioAdminDaEmpresa(Empresa $empresa): User
    {
        $token = Str::lower(Str::random(8));

        $user = User::factory()->create([
            'username' => 'cad_admin_'.$token,
            'email' => 'cad.admin.'.$token.'@example.com',
        ]);

        $user->assignRole(Perfil::ADMIN);

        $perfilAdmin = Perfil::query()->where('nome', Perfil::ADMIN)->first();

        $user->usuarioVendas()->create([
            'empresa_id' => $empresa->id,
            'perfil_id' => $perfilAdmin?->id,
            'ativo' => true,
            'data_contratacao' => now()->toDateString(),
        ]);

        return $user;
    }

    private function payloadCliente(array $overrides = []): array
    {
        return array_merge([
            'tipo' => 'PF',
            'nome' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
            'telefone' => '(11) 93333-3333',
            'celular' => '(11) 94444-4444',
            'cpf_cnpj' => '99999999999',
            'rg_ie' => '1234567',
            'endereco' => 'Rua Teste',
            'numero' => '123',
            'complemento' => 'Apto 1',
            'bairro' => 'Centro',
            'cidade' => 'Sao Paulo',
            'estado' => 'SP',
            'cep' => '01000000',
            'limite_credito' => 1000,
            'percentual_multa_atraso_padrao' => 2,
            'percentual_juros_dia_padrao' => 0.0333,
            'ativo' => true,
        ], $overrides);
    }

    private function payloadProduto(array $overrides = []): array
    {
        return array_merge([
            'codigo' => 'SKU-TESTE-01',
            'nome' => 'Produto Teste',
            'descricao' => 'Descricao de teste',
            'preco_custo' => 10,
            'preco_venda' => 20,
            'custo_medio' => 10,
            'estoque_atual' => 12,
            'estoque_minimo' => 2,
            'ativo' => true,
        ], $overrides);
    }

    private function atribuirPlanoComLimiteProdutos(int $limiteProdutos): void
    {
        $plano = Plano::query()->create([
            'nome' => 'Plano Produto '.Str::lower(Str::random(6)),
            'descricao' => 'Plano de teste para limite de produtos',
            'valor_mensal' => 99,
            'limite_usuarios' => 50,
            'limite_produtos' => $limiteProdutos,
            'permite_promissoria' => true,
            'permite_relatorios_pdf' => true,
            'permite_exportacao_xlsx' => true,
            'ativo' => true,
        ]);

        Assinatura::query()->create([
            'empresa_id' => $this->empresa->id,
            'plano_id' => $plano->id,
            'status' => 'ativa',
            'inicio_vigencia' => now()->toDateString(),
            'fim_periodo_atual' => now()->addMonth()->toDateString(),
        ]);
    }
}
